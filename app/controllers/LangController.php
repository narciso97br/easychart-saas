<?php

class LangController
{
    public function switch()
    {
        $lang = $_GET['lang'] ?? 'pt';
        
        if (in_array($lang, ['pt', 'en'])) {
            Lang::setLang($lang);
        }

        // Redirect back to the referring page or to dashboard
        $referrer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '?c=dashboard&a=index';
        header('Location: ' . $referrer);
        exit;
    }
}
