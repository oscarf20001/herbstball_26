<?php
require __DIR__ . '/../../db_connection.php';
require_once __DIR__ . '/../../../../config.php'; // Holt BASE_PATH und BASE_URL aus config.php

$currentCosts = 2700;
?>

<div id="dashboardDisplayContainer">
    <div class="display-container">
        <div class="display-container_upper">
            <h1>
                <?php
                    $herbstball_ts = 1766166300;
                    $current_ts = time();
                    $diff = $herbstball_ts - $current_ts;
                    $dayDiff = $diff / 86400;
                    $rndDayDiff = round(($diff / 86400),2,PHP_ROUND_HALF_UP);
                    echo $rndDayDiff;
                ?>
            </h1>
        </div>
        <div class="display-container_subheading">
            <p>Days left</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <div class="chart-wrapper">
                <canvas id="ticketsLeft"></canvas>
            </div>
        </div>
        <div class="display-container_subheading">
            <p>Ticketstatistik <sup style="color: lightgrey;">max. 315</sup></p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <div class="chart-wrapper">
                <canvas id="todaySold_canvas"></canvas>
            </div>
        </div>
        <div class="display-container_subheading">
            <p>heute verkauft</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <div class="chart-wrapper">
                <canvas id="weeklySold_canvas"></canvas>
            </div>
        </div>
        <div class="display-container_subheading">
            <p>Weekly Ticketverkaufhistorie</p>
        </div>
    </div>

    <!-- Platzhalter-Container -->
    <div class="display-container">
        <div class="display-container_upper">
            <canvas id="webStatisticsUserDevices_canvas"></canvas>
        </div>
        <div class="display-container_subheading">
            <p>Endgerättyp</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <canvas id="webStatisticsAccessLocations_canvas"></canvas>
        </div>
        <div class="display-container_subheading">
            <p>Access-Locations</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <h1>
                <?php
                    $stmt = $conn->prepare("SELECT SUM(paid_charges) FROM kaeufer");
                    $stmt->execute();
                    $stmt->bind_result($sum);
                    $stmt->fetch();
                    echo json_encode((float) $sum) . "€";
                    $stmt->close();
                ?>
            </h1>
        </div>
        <div class="display-container_subheading">
            <p>eingenommenes Geld</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <h1>
                <?php
                    $stmt = $conn->prepare("SELECT SUM(paid_charges) FROM kaeufer");
                    $stmt->execute();
                    $stmt->bind_result($sum);
                    $stmt->fetch();
                    echo number_format($currentCosts - (float) $sum, 2, '.', '') . '€';
                    $stmt->close();
                ?>
            </h1>
        </div>
        <div class="display-container_subheading">
            <p>fehlendes Geld <sup style="color: lightgrey;">bis Finanzierung</sup></p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <h1>
                <?php
                    $stmt = $conn->prepare("SELECT ROUND(SUM(charges) * 0.991, 2) FROM kaeufer");
                    $stmt->execute();
                    $stmt->bind_result($sum);
                    $stmt->fetch();
                    echo json_encode((float) $sum) . "€";
                    $stmt->close();
                ?>
            </h1>
        </div>
        <div class="display-container_subheading">
            <p>sollten wir einnehmen <sup style="color: lightgrey;">stand heute</sup></p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <h1>
                <?php
                    $stmt = $conn->prepare("SELECT COUNT(checked) FROM kaeufer WHERE checked = 1");
                    $stmt->execute();
                    $stmt->bind_result($sum);
                    $stmt->fetch();
                    echo json_encode((float) $sum);
                    $stmt->close();
                ?>
            </h1>
        </div>
        <div class="display-container_subheading">
            <p>confirmed tickets</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <canvas id="ageStructur_canvas"></canvas>
        </div>
        <div class="display-container_subheading">
            <p>Altersstruktur</p>
        </div>
    </div>

    <div class="display-container">
        <div class="display-container_upper">
            <canvas id="sharesOfSchool_canvas"></canvas>
        </div>
        <div class="display-container_subheading">
            <p>anteile Schulen</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/animations.js"></script>
<script src="../node_modules/chart.js/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/ticketbestand.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/todaySold.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/weeklySold.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/webStatisticsUserDevices.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/webStatisticsAccessLocations.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/ageStructur.js"></script>
<script type="module" src="<?= BASE_URL ?>/server/php/html-structure/dashboard/scripts/sharesOfSchool.js"></script>

<?php
$conn->close();
?>

<!--

c - Metis/herbstball_25/server/php/html-structure/dashboard/scripts/ticketbestand.js
i - Metis/herbstball_25/server/php/html-structure/dasboard/scripts/ticketbestand.js
-->