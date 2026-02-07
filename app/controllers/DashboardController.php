<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/PlanHelper.php';
require_once __DIR__ . '/../helpers/DataIngestionHelper.php';
require_once __DIR__ . '/../helpers/AnalysisEngine.php';

class DashboardController
{
    public function index()
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?c=auth&a=login');
            exit;
        }

        $user = $_SESSION['user'];

        $pdo = Database::getConnection();

        $error = '';
        $success = '';
        $lastChartResponse = null;
        // Agora suportamos múltiplos gráficos por requisição
        $chartsData = [];
        $analysisReportText = null;
        $analysisReportId = null;

        // Trata envio do AI Chart Generator
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prompt        = trim($_POST['prompt'] ?? '');
            $spreadsheetId = (int)($_POST['spreadsheet_id'] ?? 0);

            // Upload opcional de novo arquivo direto pelo dashboard
            if (isset($_FILES['spreadsheet']) && $_FILES['spreadsheet']['error'] === UPLOAD_ERR_OK) {
                // Respeita o mesmo limite de upload de planilhas do plano atual
                [$canUpload, $planUploadError] = PlanHelper::canUploadSpreadsheet($pdo, (int)$user['id']);
                if (!$canUpload) {
                    $error = $planUploadError;
                } else {
                    $originalName = $_FILES['spreadsheet']['name'];
                    $tmpName      = $_FILES['spreadsheet']['tmp_name'];
                    $mimeType     = $_FILES['spreadsheet']['type'];
                    $sizeBytes    = (int) $_FILES['spreadsheet']['size'];

                    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    $storedName = uniqid('sheet_', true) . '.' . $ext;

                    $storageDir = __DIR__ . '/../../storage/spreadsheets';
                    if (!is_dir($storageDir)) {
                        mkdir($storageDir, 0777, true);
                    }

                    $destPath = $storageDir . '/' . $storedName;
                    if (move_uploaded_file($tmpName, $destPath)) {
                        $stmt = $pdo->prepare('INSERT INTO spreadsheets (user_id, original_name, stored_name, mime_type, size_bytes) VALUES (:user_id, :original_name, :stored_name, :mime_type, :size_bytes)');
                        $stmt->execute([
                            'user_id'       => $user['id'],
                            'original_name' => $originalName,
                            'stored_name'   => $storedName,
                            'mime_type'     => $mimeType,
                            'size_bytes'    => $sizeBytes,
                        ]);
                        $spreadsheetId = (int)$pdo->lastInsertId();
                    } else {
                        $error = 'Failed to save uploaded spreadsheet.';
                    }
                }
            }

            if (!$error) {
                // Verifica limite de gráficos do plano atual (considerando que esta requisição pode gerar vários gráficos)
                // Por simplicidade, assumimos ao menos 1 gráfico por requisição
                [$canGenerate, $planChartError] = PlanHelper::canGenerateCharts($pdo, (int)$user['id'], 1);
                if (!$canGenerate) {
                    $error = $planChartError;
                }
            }

            if (!$error) {
                if ($spreadsheetId <= 0) {
                    $error = 'Please select or upload a file.';
                } elseif ($prompt === '') {
                    $error = 'Please describe what you want to visualize.';
                } else {
                    // Verifica limite de tokens do plano atual antes de chamar a IA
                    // Não conseguimos saber o consumo exato antes da chamada, então garantimos ao menos que há saldo.
                    [$canConsumeTokens, $planTokenError] = PlanHelper::canConsumeTokens($pdo, (int)$user['id'], 1);
                    if (!$canConsumeTokens) {
                        $error = $planTokenError;
                    }

                    if ($error) {
                        // evita chamar a IA se já estourou o limite
                        $aiPayload = [
                            'status' => 'error',
                            'error'  => $error,
                        ];
                        $lastChartResponse = $aiPayload;
                        require __DIR__ . '/../views/dashboard/index.php';
                        return;
                    }

                    // Descobre caminho do arquivo selecionado
                    $sheetStmt = $pdo->prepare('SELECT stored_name, original_name, mime_type, size_bytes FROM spreadsheets WHERE id = :id AND user_id = :uid');
                    $sheetStmt->execute(['id' => $spreadsheetId, 'uid' => $user['id']]);
                    $sheet = $sheetStmt->fetch();

                    if ($sheet) {
                        $filePath = __DIR__ . '/../../storage/spreadsheets/' . $sheet['stored_name'];

                        $ing = DataIngestionHelper::ingestFile($filePath, (string)$sheet['original_name']);
                        if (empty($ing['ok']) || empty($ing['table'])) {
                            $aiPayload = [
                                'status' => 'error',
                                'error'  => $ing['error'] ?? 'Falha ao extrair dados do arquivo.',
                            ];
                            $error = $aiPayload['error'];
                        } else {
                            $result = AnalysisEngine::run($ing['table'], $prompt);

                            $chartsList = $result['charts'] ?? [];
                            $aiPayload = [
                                'status' => 'ok',
                                'charts' => $chartsList,
                            ];

                            $analysisReportText = $result['report_text'] ?? null;

                            $datasetProfileToStore = $result['dataset_profile'] ?? null;
                            if (is_array($datasetProfileToStore) && array_key_exists('sample_rows', $datasetProfileToStore)) {
                                unset($datasetProfileToStore['sample_rows']);
                            }

                            $ins = $pdo->prepare('INSERT INTO analysis_reports (user_id, spreadsheet_id, user_request, dataset_profile_json, inferred_context_json, analytics_json, charts_json, report_text) VALUES (:uid, :sid, :req, :dp, :ic, :an, :cj, :rt)');
                            $ins->execute([
                                'uid' => (int)$user['id'],
                                'sid' => (int)$spreadsheetId,
                                'req' => $prompt,
                                'dp'  => json_encode($datasetProfileToStore, JSON_UNESCAPED_UNICODE),
                                'ic'  => json_encode($result['inferred_context'] ?? null, JSON_UNESCAPED_UNICODE),
                                'an'  => json_encode($result['analytics'] ?? null, JSON_UNESCAPED_UNICODE),
                                'cj'  => json_encode($chartsList, JSON_UNESCAPED_UNICODE),
                                'rt'  => $analysisReportText,
                            ]);
                            $analysisReportId = (int)$pdo->lastInsertId();
                        }
                    }

                    // Salva registro(s) de gráfico com payload (stub ou IA real)
                    if ($aiPayload['status'] === 'ok' && !empty($aiPayload['charts'])) {
                        $stmt = $pdo->prepare('INSERT INTO charts (user_id, spreadsheet_id, prompt, chart_type, data_json) VALUES (:user_id, :spreadsheet_id, :prompt, :chart_type, :data_json)');

                        foreach ($aiPayload['charts'] as $chartConfig) {
                            $stmt->execute([
                                'user_id'        => $user['id'],
                                'spreadsheet_id' => $spreadsheetId,
                                'prompt'         => $prompt,
                                'chart_type'     => $chartConfig['chart_type'] ?? null,
                                'data_json'      => json_encode($chartConfig),
                            ]);

                            // Prepara dados para renderização no frontend
                            $labels = isset($chartConfig['labels']) && is_array($chartConfig['labels']) ? $chartConfig['labels'] : [];
                            $valuesRaw = isset($chartConfig['values']) && is_array($chartConfig['values']) ? $chartConfig['values'] : [];
                            $values = [];
                            foreach ($valuesRaw as $v) {
                                $values[] = (float)$v;
                            }

                            if ($labels && $values && count($labels) === count($values)) {
                                $rawType = $chartConfig['chart_type'] ?? 'line';
                                $renderType = $rawType;
                                if (in_array($rawType, ['boxplot', 'gantt'], true)) {
                                    $renderType = 'bar';
                                }
                                $chartsData[] = [
                                    'type'   => $renderType,
                                    'title'  => $chartConfig['title'] ?? 'Generated chart',
                                    'labels' => $labels,
                                    'values' => $values,
                                ];
                            }
                        }
                    } else {
                        // Mesmo em modo stub, salvamos um registro simples para manter histórico
                        $stmt = $pdo->prepare('INSERT INTO charts (user_id, spreadsheet_id, prompt, chart_type, data_json) VALUES (:user_id, :spreadsheet_id, :prompt, :chart_type, :data_json)');
                        $stmt->execute([
                            'user_id'        => $user['id'],
                            'spreadsheet_id' => $spreadsheetId,
                            'prompt'         => $prompt,
                            'chart_type'     => null,
                            'data_json'      => json_encode($aiPayload),
                        ]);
                    }

                    $lastChartResponse = $aiPayload;

                    if ($aiPayload['status'] === 'ok' && !$error) {
                        $success = 'Analysis generated successfully.';
                    } elseif (!$error) {
                        $success = 'Analysis generated (stub).';
                    }
                }
            }
        }

        // Métricas simples para os cards
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM spreadsheets');
        $totalSpreadsheets = (int)$stmt->fetch()['c'];

        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM charts');
        $totalCharts = (int)$stmt->fetch()['c'];

        // Por enquanto, consideramos cada chart gerado como um "Saved Dashboard"
        $savedDashboards = $totalCharts;
        $aiInsights      = $totalCharts; // aproximar insights de charts

        // Consumo de tokens do mês atual
        $monthTokensUsed = 0;
        $monthTokenLimit = null;
        $monthTokensRemaining = null;
        try {
            $yearMonth = date('Y-m');
            $stmt = $pdo->prepare('SELECT tokens_used FROM user_token_usage_monthly WHERE user_id = :uid AND year_month = :ym LIMIT 1');
            $stmt->execute(['uid' => (int)$user['id'], 'ym' => $yearMonth]);
            $row = $stmt->fetch();
            $monthTokensUsed = $row ? (int)$row['tokens_used'] : 0;

            $planInfo = PlanHelper::getCurrentPlan($pdo, (int)$user['id']);
            $plan = $planInfo['plan'];
            if ($plan && array_key_exists('monthly_token_limit', $plan)) {
                $monthTokenLimit = $plan['monthly_token_limit'];
                if ($monthTokenLimit !== null) {
                    $monthTokensRemaining = (int)$monthTokenLimit - (int)$monthTokensUsed;
                    if ($monthTokensRemaining < 0) {
                        $monthTokensRemaining = 0;
                    }
                }
            }
        } catch (Exception $e) {
            // Mantém valores default
        }

        // Planilhas do usuário para o select
        $stmt = $pdo->prepare('SELECT id, original_name FROM spreadsheets WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute(['uid' => $user['id']]);
        $spreadsheets = $stmt->fetchAll();

        require __DIR__ . '/../views/dashboard/index.php';
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '?c=auth&a=login');
        exit;
    }
}
