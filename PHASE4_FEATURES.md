# Fase 4: Funzionalità Avanzate per la Ricerca e Gestione Memoria

## Panoramica

La Fase 4 implementa due funzionalità avanzate per migliorare il sistema di memoria a lungo termine del chatbot:

1. **Ponderazione Composita (4.1)** - Combina similarità semantica e recenza temporale
2. **Configurazione Pruning Parametrizzabile (4.2)** - Gestione avanzata della rimozione dei ricordi

## 1. Ponderazione Composita

### Funzionalità
Il sistema ora calcola un punteggio composito per ogni ricordo basato su due fattori:

- **Similarità Semantica**: Calcolata con cosine similarity sugli embedding (già presente)
- **Recenza Temporale**: Basata sul timestamp del ricordo con decadimento esponenziale

### Formula
```
scoreFinale = (similarityScore × pesoSimilarità) + (recencyScore × pesoRecenza)
```

### Configurazione Predefinita
```javascript
const MEMORY_CONFIG = {
  SIMILARITY_THRESHOLD: 0.5,    // Soglia minima per includere un ricordo
  SIMILARITY_WEIGHT: 0.7,       // Peso per la similarità (70%)
  RECENCY_WEIGHT: 0.3,          // Peso per la recenza (30%)
  ENABLE_COMPOSITE_SCORING: true // Abilita/disabilita il sistema composito
};
```

### Recency Decay (Decadimento Temporale)
Il sistema utilizza un decadimento esponenziale:
- Ricordi recenti: punteggio → 1.0
- Ricordi vecchi: punteggio → 0.01 (1% al massimo)
- Formula: `e^(-k × età)` dove k è calcolato per garantire 1% al massimo a MAX_DAYS

### Retrocompatibilità
Per disabilitare il sistema composito e tornare al comportamento originale:
```javascript
updateMemoryConfig({ ENABLE_COMPOSITE_SCORING: false });
```

Quando disabilitato, il sistema usa solo la similarità semantica (comportamento originale).

## 2. Configurazione Pruning Parametrizzabile

### Funzionalità
Il sistema di pruning ora utilizza parametri configurabili invece di valori hardcoded:

### Nuovi Limiti Predefiniti
```javascript
const MEMORY_CONFIG = {
  MAX_MEMORIES: 1500,  // Numero massimo di ricordi (era 200)
  MAX_DAYS: 365,        // Giorni massimi di retention (era 90)
  SIMILARITY_THRESHOLD: 0.5  // Soglia minima per includere ricordo
};
```

### Logica di Pruning
Il pruning avviene in due fasi:

1. **Filtro temporale**: Rimuovi tutti i ricordi più vecchi di MAX_DAYS giorni
2. **Limite numerico**: Se ancora troppi ricordi, rimuovi i più vecchi fino a MAX_MEMORIES

### Log Dettagliato
Il sistema fornisce statistiche dettagliate sul pruning:
```javascript
{
  original: 1800,           // Numero iniziale di ricordi
  timeFiltered: 1200,       // Dopo filtro temporale
  final: 1200,              // Numero finale
  removedByTime: 600,       // Rimossi per età
  removedByCount: 0         // Rimossi per eccesso
}
```

## Funzioni Utilitarie

### Visualizzare Statistiche della Memoria
```javascript
// Mostra statistiche complete nella console
displayMemoryStats();
```

Output esempio:
```
=== MEMORY STATS ===
Total memories: 450 / 1500
Retention period: 365 days
Composite scoring: ENABLED
  - Similarity weight: 0.7
  - Recency weight: 0.3
Similarity threshold: 0.5
Distribution by age:
  - Recent (<7d): 50
  - Medium (7-30d): 120
  - Old (30-90d): 180
  - Very old (>90d): 100
===================
```

### Modificare Configurazione a Runtime
```javascript
// Modifica i pesi (es. più enfasi sulla similarità)
updateMemoryConfig({ 
  SIMILARITY_WEIGHT: 0.8, 
  RECENCY_WEIGHT: 0.2 
});

// Modifica i limiti
updateMemoryConfig({ 
  MAX_MEMORIES: 2000, 
  MAX_DAYS: 730 
});

// Modifica la soglia
updateMemoryConfig({ 
  SIMILARITY_THRESHOLD: 0.6 
});
```

I pesi vengono normalizzati automaticamente se non sommano a 1.

### Ottenere Statistiche Oggetto
```javascript
const stats = getMemoryStats();
console.log(stats.total);       // Numero totale di ricordi
console.log(stats.byAge);       // Distribuzione per età
console.log(stats.compositeScoring); // Se scoring composito è attivo
```

## Monitoraggio e Debug

### Log Console
Il sistema fornisce log dettagliati per monitorare il comportamento:

1. **All'avvio**: Mostra configurazione e statistiche
2. **Durante la ricerca**: Mostra punteggi dei ricordi trovati
3. **Durante il pruning**: Mostra dettagli delle rimozioni

### Esempio di Log Ricerca
```
Found 3 relevant memories. Top scores: 
[
  { composite: "0.823", similarity: "0.750", recency: "0.987" },
  { composite: "0.654", similarity: "0.600", recency: "0.745" },
  { composite: "0.523", similarity: "0.500", recency: "0.510" }
]
```

## Esempi di Utilizzo

### Scenario 1: Aumentare Capacità di Memoria
```javascript
updateMemoryConfig({ 
  MAX_MEMORIES: 3000, 
  MAX_DAYS: 730 
});
// Pruning automatico alla prossima sessione
```

### Scenario 2: Puntare su Ricordi Più Recenti
```javascript
updateMemoryConfig({ 
  SIMILARITY_WEIGHT: 0.4, 
  RECENCY_WEIGHT: 0.6 
});
```

### Scenario 3: Tornare al Comportamento Originale
```javascript
updateMemoryConfig({ 
  ENABLE_COMPOSITE_SCORING: false,
  MAX_MEMORIES: 200,
  MAX_DAYS: 90
});
```

### Scenario 4: Ricerca con Soglia Più Alta
```javascript
// Richiede configurazione personalizzata in searchLongMemory()
// La soglia è hardcoded nella funzione ma può essere parametrizzata
```

## Note Tecniche

### Performance
- Le operazioni di embedding rimangono identiche (nessun impatto)
- Il calcolo composito è molto veloce (operazioni matematiche semplici)
- Il pruning è ottimizzato per gestire migliaia di ricordi

### Persistenza
- La configurazione è salvata in memoria (non in localStorage)
- Può essere modificata a runtime con `updateMemoryConfig()`
- Per salvare persistentemente, aggiungere MEMORY_CONFIG a localStorage

### Normalizzazione Pesi
Il sistema normalizza automaticamente i pesi se non sommano a 1:
```javascript
// Se SIMILARITY_WEIGHT=0.9 e RECENCY_WEIGHT=0.2 (totale=1.1)
// Vengono normalizzati a: SIMILARITY_WEIGHT≈0.818, RECENCY_WEIGHT≈0.182
```

## Compatibilità

### Retrocompatibile
✅ Sì - Imposta `ENABLE_COMPOSITE_SCORING: false` per comportamento originale

### Breaking Changes
❌ Nessuno - Tutte le modifiche sono retrocompatibili

### Test Consigliati
1. Testare con `ENABLE_COMPOSITE_SCORING: false` per verificare compatibilità
2. Testare con diversi valori di peso per valutare l'impatto
3. Testare il pruning con dati di test massivi

## File Modificati

- `chatbot.html` - Tutte le modifiche sono implementate in questo unico file HTML

## Prossimi Sviluppi

Possibili estensioni future:
- Salvataggio persistente della configurazione
- Interfaccia UI per modificare i parametri
- Visualizzazione grafica della distribuzione dei ricordi
- Analisi dei pattern di utilizzo per ottimizzare i pesi
