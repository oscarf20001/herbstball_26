const ctx = document.getElementById('ticketsLeft');

let soldUntilToday = await getCount();
let closedByTicketCount = 315 - soldUntilToday;

const data = {
    labels: ['Verkauft', 'Offen'],
    datasets: [{
        label: 'Tickets',
        data: [soldUntilToday, closedByTicketCount],
        backgroundColor: [
            'rgb(54, 162, 235)',
            'rgb(230, 230, 230)'
        ],
        hoverOffset: 4
    }]
};

const ticketbestand = new Chart(ctx, {
    type: 'doughnut',
    data: data,
    options: {
        responsive: true,
        maintainAspectRatio: false, // wichtig um Verzerrung zu verhindern,
        layout: {
            padding: 16
        },
        plugins: {
            legend: {
                display: true,
                position: 'left',
                align: 'center'
            },
            tooltip: {
                enabled: true
            }
        }
    }
});

async function getCount() {
    // Prüft, ob der Hostname "localhost" enthält
    const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

    try {
        const response = await fetch('../server/php/html-structure/dashboard/php/getCountTilToday.php');
        
        if (!response.ok) {
            throw new Error(`HTTP-Fehler: ${response.status}`);
        }

        const data = await response.json();

        // Wenn dein PHP-Code z. B. so etwas zurückgibt: echo json_encode(['count' => $count]);
        return data.count;

    } catch (error) {
        console.error('Fehler beim Abrufen der Anzahl:', error);
        return null;
    }
}