<?php

define("PATH_DATA", "competition.json");
define("PATH_APP", $_SERVER['PHP_SELF']);


/**
 * charger de choisir la vue à renvoyer en fonction des infos passées dans $params
 * Class Router
 */
class Router
{
    /**
     * renvoie une vue en fonction des paramètres transmis, si aucun paramètre renvoie la page d'accueil
     * @param array $params tableau des paramètres de la requête ( au plus simple $_GET, $_POST )
     * @return string
     */
    public function get(array $params)
    {
        $controller = new CompetitionController(PATH_DATA);

        if (isset($params["selectedGroupId"])){
            $groupId = $params["selectedGroupId"];
            return $controller->renderGroupView($groupId);
        }

        return $controller->renderIndexView();
    }
}

/**
 * Class CompetitionController
 * charge les données d'une compétition et expose un accès aux différentes vues nécessaires pour l'affichage de la compétition et de ses matches
 */
class CompetitionController
{
    public $competition;

    /**
     * CompetitionController constructor.
     * @param $path
     */
    function __construct($path)
    {
        $data = file_get_contents($path);
        $this->competition = new Competition(json_decode($data));
    }

    /**
     * renvoi l'affichage de la page d'accueil ( liste des groupes )
     * @return string
     */
    public function renderIndexView():string{
        $view = new IndexView($this->competition);
        return $view->render();
    }

    /**
     * renvoie l'affichage des détails d'un groupe
     * @param string $groupId
     * @return string
     */
    public function renderGroupView(string $groupId):string{
        $view = new GroupView($this->competition, $groupId);
        return $view->render();
    }
}

/**
 * Class IndexView : gestion de l'affichage de la page d'accueil
 */
class IndexView
{
    /**
     * @var Competition
     */
    private $compet;

    /**
     * IndexView constructor : initialisation des données de la compétitions
     * @param Competition $compet
     */
    public function __construct(Competition $compet)
    {
        $this->compet = $compet;
    }

    public function render():string
    {
        $view = HTMLUtils::tag('h1', $this->compet->name);

        foreach ($this->compet->groups as $group) {
            $view .= HTMLUtils::ahref(PATH_APP . '?selectedGroupId=' . $group->id, HTMLUtils::tag('h2', $group->id));
            $view .= $this->renderTeams($group);
        }

        return $view;
    }

    /**
     * affichage de la liste des équipes
     * @param $group Group
     * @return string
     */
    private function renderTeams($group):string
    {
        $view = '';
        foreach ($group->teams as $team) {
            $view .= HTMLUtils::tag('p', $team);
        }
        return $view;
    }
}

/**
 * Class GroupView : affichage de la page de détails d'un groupe ( liste  des matches )
 */
class GroupView{
    /**
     * @var Competition
     */
    private $compet;
    private $group;

    /**
     * GroupView constructor : initialisation des données
     * @param Competition $compet
     * @param string $groupId
     */
    public function __construct(Competition $compet, string $groupId )
    {
        $this->compet = $compet;

        $this->group = $compet->getGroupById($groupId); // TODO gestion d'erreur
    }

    public function render():string
    {
        $view = HTMLUtils::tag('h1', $this->compet->name);
        $view .= HTMLUtils::ahref(PATH_APP, "Retour à la liste");
        $view .= HTMLUtils::tag('h2', "Groupe " . $this->group->id);

        $view .= $this->renderMatches($this->group->teams);

        /*foreach ($this->group->teams as $team) {
            //$view .= Utils::ahref(PATH_APP . '?selectedGroupId=' . $group->id, Utils::tag('h2', $group->id));
            $view .= $this->renderMatch($teams);
        }*/

        return $view;
    }

    /**
     * @param $teams
     * @return string
     * @internal param Group $group
     */
    private function renderMatches($teams):string
    {
        $count = 0;
        $matches = array_map( function($team) use( $teams , &$count){
            $content = '';
            for($i = $count; $i < 4 ; $i++ ){
                $t = $teams[$i];
                if( $t != $team )
                    $content .= HTMLUtils::tag('p', $team . '-' . $t);
            }
            $count++;
            return $content;
        } , $teams );

        return implode($matches);

    }
}

/**
 * Class Competition : représentation des données d'une compétition : un nom et une liste de groupes
 */
class Competition
{
    public $name;

    public $groups;

    private $originalData;

    function __construct(stdClass $src)
    {
        $this->initData($src);
        $this->originalData = $src;
    }

    function __toString():string
    {
        return "Objet Competition[" . $this->name . " ( nombre groupes : " . count($this->groups) . " ')" . "]";
    }


    /**
     * initialisation des données à partir d'un objet stdClass
     * @param $src
     */
    private function initData($src)
    {
        $this->name = $src->name;

        // création des groupes
        $this->groups = [];
        foreach ($src->groups as $group) {
            $this->groups[] = new Group($group);
        }
    }

    /**
     * renvoie le groupe
     * @param $groupId
     * @return Group
     */
    public function getGroupById($groupId):Group
    {
        $selectedGroups = array_filter(
            $this->groups,
            function ($group) use ($groupId) {
                //echo $groupId." / " .$group->id.B;
                return $group->id == $groupId;
            });
        if (count($selectedGroups) > 0)
            $selectedGroup = $selectedGroups[array_keys($selectedGroups)[0]];

        return $selectedGroup;
    }
}

/**
 * Class Group : structure de données décrivant un groupe : un id et une liste d'équipes
 */
class Group
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var array
     */
    public $teams;

    public function __construct($src)
    {
        $this->initData($src);
    }

    private function initData($src)
    {
        $this->id = $src->id;
        // teams
        $this->teams = [];
        foreach ($src->teams as $team) {
            $this->teams[] = $team;
        }
    }
}

/**
 * Class Team : description d'une équipe , dans l'état seulement le nom
 */
class Team
{
    public $name;
}

/**
 * Class Utils : utilitaires HTML
 */
class HTMLUtils
{
    static public function tag(string $tag, string $str):string
    {
        return "<" . $tag . ">" . $str . "</" . $tag . ">";
    }

    /**
     * renvoie un lien html basé sur une url et un texte affiché
     * @param string $url
     * @param string $str
     * @return string
     */
    static function ahref(string $url, string $str):string
    {
        return '<a href="' . $url . '">' . $str . "</a>";
    }
}


$router = new Router();
$page = $router->get($_GET);

?>

<!doctype html>
<html lang="fr_FR">
<head>
    <meta charset="UTF-8">
    <title>UEFA</title>
</head>
<body>
<?php echo $page?>
</body>
</html>
