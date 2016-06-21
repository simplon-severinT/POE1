<?php

define("TITLE", "<h1>UEFA</h1>");
define("B", "<br/>");
define("PATH_DATA", "competition.json");
define("PATH_APP", $_SERVER['PHP_SELF']);

/**
 * stdClass : structure de données d'une competition
 */
$competition;

function initData($path)
{
    global $competition;
    $data = file_get_contents($path);
    $competition = json_decode($data);
}

/**
 * renvoie le rendu html d'une compétition
 * @return string rendu html d'une compétition
 */
function renderIndex():string
{
    initData(PATH_DATA); //TODO renommer

    $content = TITLE;

    $content .= getIndexView();

    return $content;
}

function renderHeader($compet):string
{
    return tag('h1', $compet->name);
}


/**
 * renvoie l'affichage html d'une description json de competition
 * @return string
 */
function getIndexView():string
{
    global $competition;

    $content = "";
    $content .= renderHeader($competition) /*p($competition->name)*/
    ;

    $content .= getGroupsViews($competition->groups);

    return $content;
}

/**
 * renvoie l'affichage html d'une liste de groupes
 * @param array $groups
 * @return string
 */
function getGroupsViews(array $groups):string
{
    $content = '';
    foreach ($groups as $group) {
        $content .= getGroupView($group);
    }
    return $content;
}

/**
 * renvoie le rendu html d'un groupe
 * @param $group
 * @return string
 */
function getGroupView($group)
{
    $content = ahref(PATH_APP . '?selectedGroupId=' . $group->id, $group->id);
    foreach ($group->teams as $team) {
        $content .= p("<img style='height:20px;width:30px;'  src='".$team->url."'/>".$team->nom);
    }
    return $content;
}

/**
 * rendu d'une page de groupe : nom du groupe es
 * @param string $groupId
 * @return string
 */
function renderGroupPage(string $groupId):string
{
    global $competition;

    initData(PATH_DATA);

    $idCheck = "";
    foreach ($competition->groups as $group){
        if($groupId === $group->id){
                $idCheck = $groupId;
        }
        if($idCheck !== "") {
            $selectedGroups = array_filter(
                $competition->groups,
                function ($group) use ($groupId) {
                    //echo $groupId." / " .$group->id.B;
                    return $group->id == $groupId;
                }
            );
            if( count($selectedGroups)>0 )
                $selectedGroup = $selectedGroups[array_keys($selectedGroups)[0]];
            $content = renderHeader($competition);
            $content .= ahref(PATH_APP,"Retour aux groupes" );
            //$content .= renderHeader($competition);
            $content .= renderGroupMatches($selectedGroup);
        }
        else{
            $content = "Le groupe sélectionné n'exsite pas !";
        }
    }

    return $content/*p($competition->name)*/
        ;
}

/**
 * renvoie le rendu de la liste de matchs possible ds un groupe
 * @param $group
 * @return string
 */
function renderGroupMatches($group):string
{
    $teams= $group->teams;
    $count = 0;
    $matches = array_map( function($team) use( $teams , &$count){
        $content = '';
        for($i = $count; $i < 4 ; $i++ ){
            $t = $teams[$i];
            if( $t != $team )
                $content .= p($team . '-' . $t);
        }
        $count++;
        return $content;
    } , $group->teams );

    return implode($matches);
}

/**
 * entoure un texte de balise <p>...</p>
 * @param string $str
 * @return string
 */
function p(string $str):string
{
    return '<p>' . $str . "</p>";
}

/**
 * entoure un texte d'une balise <p>...</p>
 * @param string $tag
 * @param string $str
 * @return string
 */
function tag(string $tag, string $str):string
{
    return "<" . $tag . ">" . $str . "</" . $tag . ">";
}

/**
 * renvoie un lien html basé sur une url et un texte affiché
 * @param string $url
 * @param string $str
 * @return string
 */
function ahref(string $url, string $str):string
{
    return '<a href="' . $url . '">' . $str . "</a>";
}


if (isset($_GET['selectedGroupId'])) {
    $selectedId = $_GET['selectedGroupId'];
    //echo tag('h1', $selectedId);

    $pageContent = renderGroupPage($selectedId);
} else
    $pageContent = renderIndex();

?>

<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<?php echo $pageContent ?>

</body>
</html>