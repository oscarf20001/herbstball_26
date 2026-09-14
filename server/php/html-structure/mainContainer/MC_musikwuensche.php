<div id="mainContainer">
    <h3>Wünsche:</h2>
    <ol>
        <li class="wish">
            <div class="left">Nullkommaneun - SSIO</div>
            <?php if (isset($_SESSION['logged_in'])): ?>
                <div class="right">
                    <i class="fa-solid fa-check"></i>
                </div>
            <?php endif; ?>
        </li>
        <li class="wish">
            <div class="left">City Walls - TwentyOnePilots</div>
            <?php if (isset($_SESSION['logged_in'])): ?>
                <div class="right">
                    <i class="fa-solid fa-check"></i>
                </div>
            <?php endif; ?>
        </li>
        
    </ol>

    <h3>Gepsielt:</h2>
    <ol>
        <li class="wish">Erster Wunsch</li>
        <li class="wish">Zweiter Wunsch</li>
    </ol>
</div>
