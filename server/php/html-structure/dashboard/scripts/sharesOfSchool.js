const ctx = document.getElementById('sharesOfSchool_canvas');

const dataFromBackend = await getSharesOfSchools();
const countMCG = dataFromBackend[0] ? dataFromBackend[0].anzahl : 0;
const labelMCG = dataFromBackend[0] ? dataFromBackend[0].school : '';
const countEXT = dataFromBackend[1] ? dataFromBackend[1].anzahl : 0;
const labelEXT = dataFromBackend[1] ? dataFromBackend[1].school : '';

const data = {
    labels: [labelMCG, labelEXT],
    datasets: [{
        label: 'Schule',
        data: [countMCG, countEXT],
        backgroundColor: [
            'rgb(54, 99, 235)',
            'rgb(235, 154, 54)',
            'rgba(102, 54, 235, 0.29)'
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

async function getSharesOfSchools(){
    const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

    try {
        const response = await fetch('../server/php/html-structure/dashboard/php/getSharesOfSchool.php');
        
        if (!response.ok) {
            throw new Error(`HTTP-Fehler: ${response.status}`);
        }

        const data = await response.json();

        return data;

    } catch (error) {
        console.error('Fehler beim Abrufen der Wochenstatistik:', error);
        return null;
    }
}