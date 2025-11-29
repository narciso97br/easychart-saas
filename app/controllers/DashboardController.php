<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/PlanHelper.php';

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

                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if ($ext !== 'csv') {
                        $error = 'Only CSV files are supported.';
                    } else {
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
                    // Tenta chamar API de IA, se houver API key configurada
                    $aiPayload = [
                        'status' => 'generated_stub',
                        'reason' => 'No AI call executed.',
                    ];

                    // Descobre caminho do arquivo selecionado
                    $sheetStmt = $pdo->prepare('SELECT stored_name, original_name, mime_type, size_bytes FROM spreadsheets WHERE id = :id AND user_id = :uid');
                    $sheetStmt->execute(['id' => $spreadsheetId, 'uid' => $user['id']]);
                    $sheet = $sheetStmt->fetch();

                    if ($sheet) {
                        $filePath = __DIR__ . '/../../storage/spreadsheets/' . $sheet['stored_name'];

                        // Para arquivos genéricos (PDF, DOCX, etc.), não assumimos estrutura tabular.
                        // Enviamos apenas metadados e, opcionalmente, um pequeno preview binário em base64
                        // para a IA ter contexto. A IA deve retornar os dados completos dos gráficos
                        // (labels e values) já prontos.

                        $filePreviewBase64 = null;
                        $structuredPreview = null;

                        if (is_file($filePath)) {
                            // Preview binário curto para qualquer tipo de arquivo
                            $raw = @file_get_contents($filePath, false, null, 0, 8192);
                            if ($raw !== false) {
                                $filePreviewBase64 = base64_encode($raw);
                            }

                            // Quando possível, tentamos extrair uma visão estruturada dos dados
                            $ext = strtolower(pathinfo($sheet['original_name'], PATHINFO_EXTENSION));

                            if ($ext === 'csv') {
                                // Lê o CSV inteiro (todas as linhas) para fornecer uma visão completa à IA
                                if (($handle = fopen($filePath, 'r')) !== false) {
                                    $headers = fgetcsv($handle, 0, ',');
                                    $rows    = [];
                                    while (($row = fgetcsv($handle, 0, ',')) !== false) {
                                        $rows[] = $row;
                                    }
                                    fclose($handle);

                                    if (!empty($headers) && !empty($rows)) {
                                        $structuredPreview = [
                                            'type'    => 'csv',
                                            'headers' => $headers,
                                            'rows'    => $rows,
                                        ];
                                    }
                                }
                            } elseif ($ext === 'xml') {
                                // Extrai todos os nós filhos de primeiro nível de um XML simples
                                libxml_use_internal_errors(true);
                                $xml = simplexml_load_file($filePath);
                                if ($xml !== false) {
                                    $items = [];
                                    foreach ($xml->children() as $child) {
                                        $item = [];
                                        // Atributos
                                        foreach ($child->attributes() as $k => $v) {
                                            $item[(string)$k] = (string)$v;
                                        }
                                        // Filhos de primeiro nível
                                        foreach ($child->children() as $ck => $cv) {
                                            $item[(string)$ck] = (string)$cv;
                                        }
                                        if (!empty($item)) {
                                            $items[] = $item;
                                        }
                                    }

                                    if (!empty($items)) {
                                        $structuredPreview = [
                                            'type'  => 'xml',
                                            'items' => $items,
                                        ];
                                    }
                                }
                                libxml_clear_errors();
                            } elseif (in_array($ext, ['xlsx', 'docx'], true)) {
                                // Arquivos Office: mantemos apenas metadados e preview binário.
                                // A IA deve inferir o conteúdo a partir disso.
                                $structuredPreview = [
                                    'type' => $ext,
                                    'note' => 'Office file; server did not parse full structure. Use your own parsing to infer labels from the file contents.',
                                ];
                            }
                        }

                        // Lê API key do usuário
                        $apiStmt = $pdo->prepare("SELECT api_key FROM api_configs WHERE user_id = :uid AND provider = 'openai' LIMIT 1");
                        $apiStmt->execute(['uid' => $user['id']]);
                        $apiRow = $apiStmt->fetch();

                        if ($apiRow && !empty($apiRow['api_key'])) {
                            $apiKey = $apiRow['api_key'];

                            // Monta prompt para IA
                            // Agora a IA deve devolver um objeto JSON com um array "charts",
                            // contendo todos os dados necessários para renderizar vários gráficos.
                            // Quando "structured_preview" estiver presente (ex.: CSV/XML), a IA
                            // deve usar os valores reais (por exemplo, nomes de produtos) como labels,
                            // evitando rótulos genéricos como "Product A", "Product B".
                            $systemPrompt = 'You are an assistant that designs ONE OR MORE chart configurations '
                                . 'based on a user request and the contents of an uploaded file. '
                                . 'You receive file metadata, an optional base64 preview, and sometimes a "structured_preview" '
                                . 'containing real headers and sample rows (for CSV/XML). '
                                . 'When structured_preview is present, ALWAYS use the real values from it as labels '
                                . '(for example, real product names or dates) instead of generic names like "Product A". '
                                . 'Return ONLY a JSON object with a top-level "charts" array. '
                                . 'Each item in "charts" MUST have the fields: '
                                . 'chart_type (string, e.g. line, bar), '
                                . 'title (string), '
                                . 'description (string), '
                                . 'labels (array of x-axis labels as strings), '
                                . 'values (array of numeric values, same length as labels).';

                            $userPrompt = [
                                'user_request'       => $prompt,
                                'file_name'          => $sheet['original_name'],
                                'mime_type'          => $sheet['mime_type'],
                                'size_bytes'         => (int)$sheet['size_bytes'],
                                // Pequeno preview binário em base64 apenas como contexto opcional
                                'file_preview_base64' => $filePreviewBase64,
                                // Pré-visualização estruturada (quando disponível: CSV/XML/Office)
                                'structured_preview' => $structuredPreview,
                            ];

                            $payload = [
                                'model' => 'gpt-4o-mini',
                                'messages' => [
                                    ['role' => 'system', 'content' => $systemPrompt],
                                    ['role' => 'user', 'content' => json_encode($userPrompt)],
                                ],
                                'response_format' => ['type' => 'json_object'],
                            ];

                            $ch = curl_init('https://api.openai.com/v1/chat/completions');
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_POST => true,
                                CURLOPT_HTTPHEADER => [
                                    'Content-Type: application/json',
                                    'Authorization: Bearer ' . $apiKey,
                                ],
                                CURLOPT_POSTFIELDS => json_encode($payload),
                            ]);

                            $responseBody = curl_exec($ch);
                            $curlErr      = curl_error($ch);
                            $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);

                            if ($curlErr) {
                                $aiPayload = [
                                    'status' => 'error',
                                    'error'  => 'Curl error: ' . $curlErr,
                                ];
                                $error = 'Failed to contact AI service.';
                            } elseif ($httpCode < 200 || $httpCode >= 300) {
                                $aiPayload = [
                                    'status' => 'error',
                                    'error'  => 'HTTP ' . $httpCode,
                                    'body'   => $responseBody,
                                ];
                                $error = 'AI service returned an error.';
                            } else {
                                $decoded = json_decode($responseBody, true);
                                $content = $decoded['choices'][0]['message']['content'] ?? null;
                                if ($content) {
                                    $parsed = json_decode($content, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        // Normalizamos a resposta para sempre ter um array "charts"
                                        $chartsList = [];
                                        if (isset($parsed['charts']) && is_array($parsed['charts'])) {
                                            $chartsList = $parsed['charts'];
                                        } elseif (is_array($parsed) && isset($parsed['chart_type'])) {
                                            // Compatibilidade com resposta de um único gráfico
                                            $chartsList = [$parsed];
                                        }

                                        $aiPayload = [
                                            'status' => 'ok',
                                            'charts' => $chartsList,
                                        ];
                                    } else {
                                        $aiPayload = [
                                            'status' => 'error',
                                            'error'  => 'Invalid JSON from AI.',
                                            'raw'    => $content,
                                        ];
                                        $error = 'AI response could not be parsed.';
                                    }
                                } else {
                                    $aiPayload = [
                                        'status' => 'error',
                                        'error'  => 'No content from AI.',
                                        'raw'    => $decoded,
                                    ];
                                    $error = 'AI did not return content.';
                                }
                            }
                        } else {
                            // sem API key, mantemos stub
                            $aiPayload = [
                                'status' => 'generated_stub',
                                'reason' => 'No API key configured. Set it in Settings > API Configuration.',
                            ];
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
                                $chartsData[] = [
                                    'type'   => $chartConfig['chart_type'] ?? 'line',
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
                        $success = 'Charts generated successfully with AI.';
                    } elseif (!$error) {
                        $success = 'Chart generated (stub). Configure API key in Settings for AI-powered charts.';
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
