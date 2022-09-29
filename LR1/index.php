<?php 
  
/**
 * Для обработки входных данных
 */      
class MonitorRecommender
{
    public array $questions = [];
    private array $terms = [];
    public array $monitors = [];
    public array $results = [];

    function __construct(string $conditionsFilepath, string $monitorsFilepath)
    {
        $this->parseConditions($conditionsFilepath);
        $this->parseMonitors($monitorsFilepath);
        $this->processForm();
    }

    private function parseConditions(string $filepath): void
    {
        $lines = file($filepath);
        $conditions = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $results = explode("то", $line)[1];
            preg_match_all('/\(.*?\)/', $line, $strConditions);
            $questions = [];
            foreach ($strConditions[0] as $cond) {
                [$question, $answer] = explode('=', trim($cond, "()"));
                
                if (!array_key_exists($question, $questions)) {
                    $questions[$question] = [];
                } else if (!in_array($answer, $questions[$question])) {
                    $questions[$question] = $answer;
                }
            }
        }

        array_push($strConditions, $results);
        array_push($this->terms, $strConditions);
        var_dump($results);
        echo "<br><br><br><br>";
        var_dump($this->terms);
        $this->questions = $questions;
    }

    private function parseMonitors(string $filepath): void
    {
        $lines = file($filepath);
        $monitors = [];
        foreach ($lines as $line) {
            $line = explode(":", trim($line));
            $name = $line[0];
            $features = explode(",", $line[1]);
            $monitors[] = [$name, $features];
        }
        $this->monitors = $monitors;
    }

    private function processForm(): void
    {
        $keys = array_keys($this->questions);

        $answers = [];
        for ($i = 0; $i < count($this->questions); $i++) {
            if (isset($_POST['question'.$i])){
                $answers[$keys[$i]] = trim($keys[$i], " ")."=".$_POST['question'.$i];
            }
        }

        $results = []; 
        $match = true;
        foreach($this->terms as $term) {
            foreach($term[0] as $el) {
                if(!in_array($el, $answers)) {
                    $match = false;
                    break;
                }
            }

            if ($match === true) {
                if (in_array(trim(explode("=",$term[1])[0], " "), $keys, true)) {
                    $answers[trim(explode("=",$term[1])[0], " ")] = trim($term[1], " ");
                }
                $results[] = $term[1];
            }
            $match = true;
        }

        $this->results = $results;
    }
}

/**
 * Для вывода данных на экран
 */
class PageBuilder
{
    
    function __construct()
    {
    }

    public static function echoHead(): void
    {
        echo "<!DOCTYPE html><html lang='ru'>";
        echo "<head> <link rel=\"canonical\" href=\"https://getbootstrap.com/docs/5.2/examples/sign-in/\">  </head> <link href=\"https://getbootstrap.com/docs/5.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\" integrity=\"sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT\" crossorigin=\"anonymous\">" ;
        echo '<link rel="stylesheet" type="text/css" href="style.css"/>';
        echo "<div style=\"background: #1e3d59\">";
        echo "<h1 class='text-center pt-3' style=\"color: white\"> Выбор монитора для компьютера </h1>";
        echo "<h5 class='text-center mt-3 mb-4' style=\"color: white\"> Эта анкета поможет вам определиться с выбором конкретного монитора </h5>";
        echo "<div class='text-center w-50 mx-auto pb-3' style=\"color: white\">";
        echo "Вам нужно выбрать, каким критериям должен соответствовать монитор, после этого вам будут показаны рекомендации по выбору";
        echo "</div>";
        echo "</div>";
        echo "<div style=\"background: #f5f0e1\">";
        echo "<h3 class='text-center pt-3 pb-3'> Выберите критерии </h3>";
    }

    public static function echoForm($questions)
    {
        echo "<form name='question_form' class='form text-center container' method='POST'>";
        $count = 0;
        foreach($questions as $question => $answers){
            self::echoFormSelection($count, $question, $answers);
            $count++;
        }
        echo "<button class=\"w-2 btn btn-lg btn-primary\">Отправить</button>
            </form><br><br>";
    }

    private static function echoFormSelection($n, $question, $answers){
        echo "<label>".$question."</label> ";
        echo "<select class='form-select' style='max-width: 400px; margin-left: 350px;' name='question".$n."'>";
        echo "<option> - </option>";

        foreach($answers as $answer){
            echo "<option>".$answer."</option>";
        }

        echo "</select><br><br>";
    }

    public static function echoResults($monitors)
    {
        echo "<div class='text-center'>";
        echo '<h1 class="mt-5"> Результат </h1>';

        foreach($monitors as &$monitor){
            $count = 0;
            for($i=0; $i < count($monitors); $i++){
                if(in_array(substr($monitors[$i], 1), $monitor[1])){
                    $count++;
                }
            }
            $monitor[2] = ((float)$count)/((float)count($monitor[1]));
        }

        echo '<table class="table w-50 mx-auto">';
        echo '<thead><tr><th>Монитор</th> <th>Вероятность</th></tr></thead>';

        foreach($monitors as $monitor){
            echo '<tr>';

            echo"<th>" . $monitor[0] . "</th>" . "<th>" . round($monitor[2], 2) . "% </th>";
            echo '</tr>';
        }

        echo '</table>';
        echo"<br><br><br><br>";
    }
}

$recommender = new MonitorRecommender('conditions.txt', 'monitors.txt');
PageBuilder::echoHead();
PageBuilder::echoForm($recommender->questions);
PageBuilder::echoResults($recommender->results);
