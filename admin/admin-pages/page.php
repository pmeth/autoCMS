<?php
include_once('header.php');
?>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h2 class="page-header">Content editing for <?=$page?> page</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <form action="/admin/page/<?=$page?>/update/" method="post" class="form-horizontal" enctype="multipart/form-data">
                <?php foreach($data as $key => $datum) { ?>
                    <div class="form-group">
                        <?php $desc = $datum['description']; ?>
                        <label for="<?=$key?>" class="col-lg-2 col-sm-2 control-label">
                            <a id="desc-<?=$key?>" class="desc-edit dirtyOK" data-type="text" data-pk="<?=$key?>" data-url="/admin/page/<?=$page?>/desc/" data-title="edit description"><?=$desc?></a>
                        </label>
                        <?php if ($datum['type'] == 'html') { ?>
                            <div class="col-lg-9 col-sm-10 textarea">
                                <textarea name="<?=$key?>" class="form-control editor"><?=$datum['html']?></textarea>
                            </div>
                        <?php } else if ($datum['type'] == 'text') { ?>
                            <div class="col-lg-9 col-sm-10">
                                <input type="text" name="<?=$key?>" class="form-control" value="<?=$datum['text']?>" autocomplete="off">
                            </div>
                        <?php } else if ($datum['type'] == 'link') { ?>
                            <div class="col-lg-9 col-sm-10">
                                <input type="text" name="<?=$key?>" class="form-control" value="<?=$datum['link']?>" autocomplete="off">
                            </div>
                        <?php } else if ($datum['type'] == 'color') { ?>
                            <div class="col-lg-9 col-sm-10">
                                <div class="input-group color-picker" data-align="left" data-format="rgba">
                                    <input name="<?=$key?>" type="text" value="<?=$datum['color']?>" class="form-control" autocomplete="off">
                                    <span class="input-group-addon"><i></i></span>
                                </div>
                            </div>
                        <?php } else if ($datum['type'] == 'image') { ?>
                            <div class="col-lg-7 col-sm-7">
                                <img id="<?=$key?>-image" class="img-responsive img-thumbnail" src="<?=$datum['image']?>">
                            </div>
                            <div class="col-lg-2 col-sm-3">
                                <input type="file" name="<?=$key?>" id="<?=$key?>" style="display: none;" onchange="readURL(this, '<?=$key?>');">
                                <button type="button" class="btn btn-info btn-block upload-button dirtyOK" data-trigger="<?=$key?>">Upload Image</button>
                            </div>
                        <?php } else if ($datum['type'] == 'repeat') { ?>
                            <div class="col-lg-9 col-sm-10">
                                <a href="/admin/page/<?=$page?>/repeat/<?=$key?>/" class="btn btn-success btn-block">Edit Repeat (<?=count($datum['repeat'])?>)</a>
                            </div>
                        <?php } else if ($datum['type'] == 'blog-count') { ?>
                            <div class="col-lg-9 col-sm-10">
                                <div class="input-group">
                                    <span class="input-group-addon">Blog List Display Count</span>
                                    <input type="number" name="<?=$key?>" class="form-control" value="<?=$datum['blog-count']?>" autocomplete="off">
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
                <hr>
                <div class="form-group">
                    <div class="col-lg-offset-9 col-lg-2 col-sm-offset-9 col-sm-3">
                        <button type="submit" class="btn btn-primary btn-block pull-right dirtyOK">Save Page</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <br><br>
</div>
<?php
include_once('footer.php');
?>