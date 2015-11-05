<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -16)); //strlen('survey/index.php')

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/core.php';
require_once SC_ROOT.'src/func/common.php';

$sustc = new core();
$b = $sustc->base;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$js_survey = '';
$html_title = '';
$js_storage_answer = '';

if ($action == '') {
	if ($id != 0) {
    if (isset($_SESSION['survey']) && isset($_SESSION['survey'][$id]) && isset($_SESSION['survey'][$id]['answer'])) {
      $js_storage_answer = $_SESSION['survey'][$id]['answer'];
    }
		$survey = $b->db->fetch_first("SELECT * FROM `sustc_survey` WHERE `id` = $id AND `disabled` = 0;");
		if ($survey) {
      $html_title = $survey['title'];
      $survey['question'] = json_decode($survey['question'], TRUE);
      $js_survey = json_encode($survey, JSON_UNESCAPED_UNICODE);
    }
	}
  include template('survey/index.html');
} else if ($action == 'submit') {
  $r = array('errno' => 0, 'msg' => 'Success');
  $answer = $_POST['answer'];
  if ($id <= 0) {
    $r['errno'] = 2;
    $r['msg'] = 'Error id';
  } else if ($answer) {
    $json_answer = json_decode($answer, TRUE);
    if ($json_answer) {
      $survey = $b->db->fetch_first("SELECT * FROM `sustc_survey` WHERE `id` = $id AND `disabled` = 0;");

      if ($survey) {
        $question = json_decode($survey['question'], TRUE);
        $unique_answer = array();

        $find_require_error = false;
        foreach ($question as $key => $value) {
          if (array_key_exists('require', $question[$key]) && $question[$key]['require'] && !$json_answer[$key]) {
            $find_require_error = true;
          }
        }

        if ($find_require_error) {
          $r['errno'] = 3;
          $r['msg'] = 'required value not found';
        } else {
          foreach ($json_answer as $key => $value) {
            if (array_key_exists($key, $question) && array_key_exists('unique', $question[$key]) && $question[$key]['unique']) {
              $unique_answer[$key] = $value;
            }
          }

          $answer = false;
          $sql_unique_answer = '';
          if ($unique_answer) {
            $sql_unique_answer = addslashes(json_encode($unique_answer, JSON_UNESCAPED_UNICODE));
            $answer = $b->db->fetch_first('SELECT * FROM `sustc_survey_data` WHERE `unique_answer` = "'.$sql_unique_answer.'";');
          } else if (isset($_SESSION['survey'][$id]['data_id'])) {
            $data_id = intval($_SESSION['survey'][$id]['data_id']);
            $answer = $b->db->fetch_first('SELECT * FROM `sustc_survey_data` WHERE `id` = "'.$data_id.'";');
          }

          $s_answer = json_encode($json_answer, JSON_UNESCAPED_UNICODE);

          $_SESSION['survey'] = array();
          $_SESSION['survey'][$id] = array('answer' => $s_answer);

          $sql_answer = addslashes($s_answer);
          
          if ($answer) {
            $b->db->query('UPDATE `sustc_survey_data` SET `answer` = "'.$sql_answer.'" WHERE `survey_id` = '.$id.' AND `id` = "'.$answer['id'].'";');
            $_SESSION['survey'][$id]['data_id'] = $answer['id'];
          } else {
            $b->db->query("UPDATE `sustc_survey` SET `answer_count` = `answer_count` + 1 WHERE `id` = $id AND `disabled` = 0;");
            $b->db->query('INSERT INTO `sustc_survey_data` SET `survey_id` = '.$id.', `unique_answer` = "'.$sql_unique_answer.'", `answer` = "'.$sql_answer.'";');
            $_SESSION['survey'][$id]['data_id'] = $b->db->insert_id();
          }

          $r['errno'] = 0;
        }
      } else {
        $r['errno'] = 2;
        $r['msg'] = 'id not found';
      }
    }
  } else {
    $r['errno'] = 2;
    $r['msg'] = 'no answer';
  }
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
} else if ($action == 'charts') {
  $answer_count = 0;
  if ($id != 0) {
    $survey = $b->db->fetch_first("SELECT * FROM `sustc_survey` WHERE `id` = $id AND `disabled` = 0;");
    if ($survey) {
      $html_title = $survey['title'];
      $survey['question'] = json_decode($survey['question'], TRUE);   

      $answer = DB::fetch_all("SELECT * FROM `sustc_survey_data` WHERE `survey_id` = $id;");
      $answer_count = count($answer);
      //$js_survey_data = json_encode($answer, JSON_UNESCAPED_UNICODE);

      if ($answer) {
        $question = $survey['question'];

        $field = array();
        $field_data = array();
        $field_count = 0;

        foreach ($question as $key => $value) {
          if (isset($value['type'])) {
            switch ($value['type']) {
              case 'boolean':
              case 'range':
              case 'rating':
              case 'select':
                $field[$key] = $value;
                $field_count++;
                break;
            }
          }
        }

        for ($i = 0; $i < count($answer); $i++) {
          $cur_answer = json_decode($answer[$i]['answer'], TRUE);

          foreach ($field as $key => $f) {
            if (array_key_exists($key, $cur_answer)) {
              $value = $cur_answer[$key];
              if (!isset($field_data[$key])) {
                $field_data[$key] = array();
              }
              $field_data[$key][] = $value;
            }
          }
        }

        $js_survey_charts = json_encode($field, JSON_UNESCAPED_UNICODE);
        $js_survey_data = json_encode($field_data, JSON_UNESCAPED_UNICODE);
      }

    }
  }
  include template('survey/charts.html');
} else if ($action == 'export') {
  $r = array('errno' => 2, 'msg' => '');
  if ($id > 0) {
    $survey = $b->db->fetch_first("SELECT * FROM `sustc_survey` WHERE `id` = $id AND `disabled` = 0;");
    if ($survey) {
      $answer = false;
      $uid = intval($_G['uid']);
      if (!$uid || $uid !== intval($survey['uid'])) {
        $r['msg'] = 'access denied';
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
        exit();
      }
      if (intval($survey['answer_count']) > 0) {
        $r['errno'] = 0;

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename='.$id.'.csv');
        
        $question = json_decode($survey['question'], TRUE);
        $answer = DB::fetch_all("SELECT * FROM `sustc_survey_data` WHERE `survey_id` = $id;");

        $field = array();
        $field_count = 0;
        foreach ($question as $key => $value) {
          $field[$field_count] = $key;
          $field_count++;
        }

        echo "\xEF\xBB\xBF";
        for ($i = 0; $i < count($answer); $i++) {
          $cur_answer = json_decode($answer[$i]['answer'], TRUE);
          for ($j = 0; $j < $field_count; $j++) {
            if ($j != 0) {
              echo ',';
            }
            if (array_key_exists($field[$j], $cur_answer)) {
              $value = $cur_answer[$field[$j]];
              if (is_array($value)) {
                for ($k = 0; $k < count($value); $k++) {
                  if ($k != 0) {
                    echo ' ';
                  }
                  echo $value[$k];
                }
              } else {
                $value = preg_replace("/\\r?\\n/", "; ", $value);
                echo $value;
              }
            }
          }
          echo "\n";
        }
        exit();
      }
      $r['msg'] = 'answer not found';
    } else {
      $r['msg'] = 'id not found';
    }
  } else {
    $r['msg'] = 'Error id';
  }
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
} else if ($action == 'answercount') {
  $r = array('errno' => 2, 'msg' => '');
  if ($id > 0) {
    $survey = $b->db->fetch_first("SELECT * FROM `sustc_survey` WHERE `id` = $id AND `disabled` = 0;");
    if ($survey) {
      $r['errno'] = 0;
      $r['answer_count'] = $survey['answer_count'];
    } else {
      $r['msg'] = 'id not found';
    }
  } else {
    $r['msg'] = 'Error id';
  }
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
} else {
  $r = array('errno' => 1, 'msg' => 'Unknow action');
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
}

?>