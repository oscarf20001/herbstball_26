const ctx = document.getElementById('webStatisticsUserDevices_canvas');

const backEndData = await countDesktopAccesses()
const mobileCount = backEndData[0] ?  backEndData[0].anzahl : 0;
const desktopCount = backEndData[1] ?  backEndData[1].anzahl : 0;

const data = {
    labels: ['Mobile', 'Desktop'],
    datasets: [{
        label: 'Device',
        data: [mobileCount, desktopCount],
        backgroundColor: [
            'rgb(190, 54, 235)',
            'rgb(235, 196, 54)'
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

async function countDesktopAccesses(){
    const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

    try {
            const response = await fetch('../server/php/html-structure/dashboard/php/DekstopAccessCount.php');
            
            if (!response.ok) {
            throw new Error(`HTTP-Fehler: ${response.status}`);
            }

            const data = await response.json();

            // Wenn dein PHP-Code z. B. so etwas zurückgibt: echo json_encode(['count' => $count]);
            return data;

    } catch (error) {
        console.error('Fehler beim Abrufen der Anzahl:', error);
        return null;
    }
}