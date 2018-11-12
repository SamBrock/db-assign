<?php
try{
    $conn = new PDO('mysql:host=localhost;dbname=u1663363', 'root', '');
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch (PDOException $exception)
{
    echo "Oh no, there was a problem" . $exception->getMessage();
}

if(isset($_GET['q'])){
    $searchterm=$_GET["q"];
}
if(isset($_GET['t'])){
    $team=$_GET["t"];
}
if(isset($_GET['p'])){
    $position=$_GET["p"];
}

$query = "SELECT DISTINCT players.first_name, players.last_name, players.id as player_id, teams.id as team_id, players.number, teams.abbreviation, ROUND(SUM(points / games), 1) as PTS, ROUND(SUM((offensive_rebounds + defensive_rebounds) / games),1) as REB, ROUND(SUM(assists / games), 1) as AST, ROUND(SUM(blocks / games), 1) as BLK
FROM players INNER JOIN player_position ON players.id=player_position.player_id INNER JOIN positions ON player_position.position_id=positions.id INNER JOIN statistics ON players.id=statistics.player_id INNER JOIN teams ON players.team_id=teams.id
WHERE ((first_name LIKE :searchterm OR last_name LIKE :searchterm OR CONCAT(first_name,' ', last_name) LIKE :searchterm) OR :searchterm IS NULL)
AND (teams.abbreviation = :team OR :team IS NULL)
AND (positions.name = :position OR :position IS NULL)
GROUP BY last_name, positions.name";
$prep_stmt=$conn->prepare($query);
$prep_stmt->bindValue(':searchterm', '%' . $searchterm . '%');
$prep_stmt->bindValue(':team', $team);
$prep_stmt->bindValue(':position', $position);

$prep_stmt->execute();
$players=$prep_stmt->fetchAll();

$count = count($players);

$pos_q = "SELECT players.id as player_id, positions.name
FROM players INNER JOIN player_position ON players.id=player_position.player_id INNER JOIN positions ON player_position.position_id=positions.id";
$pos_stmt=$conn->prepare($pos_q);

$pos_stmt->execute();
$positions=$pos_stmt->fetchAll();

?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php if(isset($_GET['q'])){ echo "{$searchterm} - "; }  ?>NBA Player Search</title>
        <link href="style/style.css" rel="stylesheet" type="text/css">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    </head>
    <body>
        <header>
            <div class="header-inner">
                <img class="logo" src="img/nba-logo.png">
                <div class="logo-txt">NBA PLAYER SEARCH</div>
                <nav>
                    <ul>
                        <li><a href="index.php">Search</a></li>
                        <li><a href="design.php">Design</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <main>
            <div class="search-container">
                <form action="index.php" method="GET" id="search-form">
                    <div class="search-box">
                        <input type="text" class="form-control" name="q" id="search" placeholder="Search by player name" value="<?php if(isset($_GET['q'])){ echo "$searchterm"; } ?>"><button type="sumbit" class="search-btn"></button>
                    </div>
                    <div class="filters">
                        <select name="t" placeholder="Team" id="team-select" class="form-control" >
                            <option value="">Team</option>
                            <option value="ATL">Atlanta Hawks</option>
                            <option value="BOS">Boston Celtics</option>
                            <option value="BKN">Brooklyn Nets</option>
                            <option value="CHA">Charlotte Hornets</option>
                            <option value="CHI">Chicago Bulls</option>
                            <option value="CLE">Cleaveland Cavaliers</option>
                            <option value="DAL">Dallas Mavericks</option>
                            <option value="DEN">Denver Nuggets</option>
                            <option value="DET">Detroit Pistons</option>
                            <option value="GSW">Golden State Warriors</option>
                            <option value="HOU">Houston Rockets</option>
                            <option value="IND">Indiana Pacers</option>
                            <option value="LAC">Los Angeles Clippers</option>
                            <option value="LAL">Los Angeles Lakers</option>
                            <option value="MEM">Memphis Grizzlies</option>
                            <option value="MIA">Miami Heat</option>
                            <option value="MIL">Milwaukee Bucks</option>
                            <option value="MIN">Minnesota Timberwolves</option>
                            <option value="NOP">New Orleans Pelicans</option>
                            <option value="NYK">New York Knicks</option>
                            <option value="OKC">Oklahoma City Thunder</option>
                            <option value="ORL">Orlando Magic</option>
                            <option value="PHI">Philadelphia 76ers</option>
                            <option value="PHX">Phoenix Suns</option>
                            <option value="POR">Portland Trail Blazers</option>
                            <option value="SAC">Sacramento Kings</option>
                            <option value="SAS">San Antonio Spurs</option>
                            <option value="TOR">Toronto Raptors</option>
                            <option value="UTA">Utah Jazz</option>
                            <option value="WAS">Washington Wizards</option>
                        </select>
                        <select name="p" placeholder="Position" id="position-select" class="form-control">
                            <option value="">Position</option>
                            <option value="G">Guard</option>
                            <option value="F">Forward</option>
                            <option value="C">Center</option>
                        </select>
                        <script>
                            <?php if(isset($_GET['t'])){ ?>
                            document.getElementById('team-select').value = "<?php echo "$team";?>";
                            <?php } if(isset($_GET['p'])){ ?>
                            document.getElementById('position-select').value = "<?php echo "$position";?>";
                            <?php } ?>
                        </script>
                    </div>
                </form>
            </div>
            <?php if(isset($_GET['q']) || isset($_GET['t']) || isset($_GET['p'])){ ?>
            <div class="results-container">
                <div class="results">
                    <div class="results-header">
                        <span class="results-num"><?php echo $count; ?> results</span>
                        <div class="result-stat-header">
                            <span>PTS</span>
                            <span>REB</span>
                            <span>AST</span>
                            <span>BLK</span>
                        </div>
                    </div>
                    <?php
                      foreach($players as $player){
                          echo "<div class='result-player'>";
                          echo "<div class='result-player-name'><a href='details.php?id={$player["player_id"]}'>{$player["first_name"]} {$player["last_name"]}</a></div>";
                          echo "<div class='result-player-info'>#{$player["number"]} | ";
                          foreach($positions as $pos){
                             if($pos["player_id"] == $player["player_id"]){
                                 echo "<span>{$pos["name"]}</span>";
                             }
                          }
                          echo " | {$player["abbreviation"]}</div>";
                          echo "<div class='result-player-stats'><span class='result-stat'>{$player["PTS"]}</span><span class='result-stat'>{$player["REB"]}</span><span class='result-stat'>{$player["AST"]}</span><span class='result-stat'>{$player["BLK"]}</span></div>";
                          echo "</div>";
                      }
                    ?>
                </div>
            </div>
            <?php } ?>
        </main>
        <script src="js/js.js"></script>
    </body>
</html>
