<?php

class AnalyticsData extends Data {
    public $dataFile = 'autocms-analytics.json';

    public function createFile() {
        if (!file_exists($this->dataLoc . $this->dataFile)) {
            $this->data = Array();
            $this->data['analytics'] = Array('analytics' => '', 'description' => 'analytics code', 'type' => 'analytics');
            $fp = fopen($this->dataLoc . $this->dataFile, 'w');
            fwrite($fp, json_encode($this->data));
            fclose($fp);
        }
    }

    public function getAnalyticsData() {
        return $this->data;
    }

    public function updateAnalytics($data) {
        $changeLog = Array();

        foreach ($data as $key => $datum) {
            if ($key != 'key' && isset($this->data[$key]) && $this->data[$key][$this->data[$key]['type']] != trim($datum)) {
                $changeLog[] = Array('key' => $key, 'change' => Array('original' => $this->data[$key][$this->data[$key]['type']], 'new' => trim($datum)));
                $this->data[$key][$this->data[$key]['type']] = trim($datum);
            }
        }

        if (count($changeLog) > 0) addToLog('has updated', ' the analytics code', $changeLog);
    }

}

class Analytics {
    function get() {
        $users = new UsersData();
        if ($users->checkPass() && !$users->authNeeded()) {
            include_once('admin-pages/analytics.php');
        } else {
            include_once('401.html');
        }
    }
    function post() {
        $users = new UsersData();
        if ($users->checkPass() && !$users->authNeeded()) {

            $analytics = new AnalyticsData();
            $analytics->updateAnalytics($_POST);

            header('Location: /admin/analytics/?updated=true');
        } else {
            include_once('401.html');
        }
    }
}
