<?php
/**
 * exportMemory.php
 * Esporta la memoria a lungo termine memorizzata in memoria_salvata.json in formato TXT.
 */

$filename = 'memoria_salvata.json';

if (file_exists($filename)) {
    $json = file_get_contents($filename);
    $memoria = json_decode($json, true);
    
    if ($memoria === null) {
        http_response_code(500);
        echo "Errore: Impossibile decodificare il file di memoria.";
        exit;
    }
    
    $output = "";
    foreach ($memoria as $item) {
        // Recupera il testo, supportando diverse varianti di nomi campo
        $text = $item['testo'] ?? "";
        if (empty($text) && isset($item['request']) && isset($item['response'])) {
            $text = "UTENTE: " . $item['request'] . "\nBOT: " . $item['response'];
        } else {
            // Normalizza i prefissi Utente/Bot in UTENTE/BOT
            $text = str_replace(["Utente:", "Bot:"], ["UTENTE:", "BOT:"], $text);
        }
        
        $data = $item['data'] ?? $item['timestamp'] ?? "N/A";
        $output .= "[$data] $text\n---\n";
    }
    
    $dateStr = date("Ymd_His");
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="memoria_completa_' . $dateStr . '.txt"');
    echo $output;
} else {
    http_response_code(404);
    echo "Errore: Il file memoria_salvata.json non esiste sul server.";
}
?>
