<?php
include_once('header.php');
$getUserLogs = null;
if (isset($_GET['user-logs'])) $getUserLogs = $_GET['user-logs'];
$logs = getLogData(0, null, $getUserLogs);
?>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h2 class="page-header"><?php if (!is_null($getUserLogs)) { print $getUserLogs . '\'s '; } ?>Logs for CMS Recent History</h2>
        </div>
        <!-- /.col-lg-12 -->
    </div>

    <div class="row">
        <div class="col-lg-12">
            <?php foreach($logs as $log) { ?>
                <div class="media">
                    <div class="media-left">
                        <div style="position:relative;">
                            <div style="position: absolute; top: 2px; width: 100%; text-align: center; font-size: 12px; z-index: 2; color: #fff;"><?=date("M", $log['timestamp'])?></div>
                            <div style="position: absolute; top: 15px; width: 100%; text-align: center; font-size: 30px; z-index: 2;"><?=date("j", $log['timestamp'])?></div>
                            <img class="media-object" src="/admin/img/ic_calendar.png" alt="<?=$log['timestamp']?>" style="width: 60px;">
                        </div>
                    </div>
                    <div class="media-body">
                        <h4 class="media-heading"><?=$log['user']?></h4>
                        <?=$log['action']?> <?=$log['page']?><br>
                        <?php if (!is_null($log['details'])) echo htmlentities(serialize($log['details'])); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <br><br>
</div>
<?php
include_once('footer.php');
?>