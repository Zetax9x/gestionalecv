// Funzioni specifiche per la dashboard
window.updateStats = function() {
    fetch('/api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            console.log('Stats aggiornate:', data);
        });
};

// Auto-refresh ogni 5 minuti
setInterval(window.updateStats, 300000);
