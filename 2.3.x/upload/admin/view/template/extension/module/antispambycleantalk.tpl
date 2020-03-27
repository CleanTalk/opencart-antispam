<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-store" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $heading_title; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-store" class="form-horizontal">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-enable"><span data-toggle="tooltip" title="<?php echo $help_enable; ?>"><?php echo $entry_enable; ?></span></label>
                <div class="col-sm-10">
                  <select name="module_antispambycleantalk_status" id="antispambycleantalk-enable" class="form-control">
          <?php if ($module_antispambycleantalk_status) { ?>          
          <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
          <option value="0"><?php echo $text_disabled; ?></option>
          <?php } else { ?>
          <option value="1"><?php echo $text_enabled; ?></option>
          <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
          <?php } ?>
                  </select>
                </div>
            </div>
      <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-check_registrations"><span data-toggle="tooltip" title="<?php echo $help_check_registrations; ?>"><?php echo $entry_check_registrations; ?></span></label>
                <div class="col-sm-10">
                  <select name="module_antispambycleantalk_check_registrations" id="antispambycleantalk-check_registrations" class="form-control">
          <?php if ($module_antispambycleantalk_check_registrations) { ?>
          <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
          <option value="0"><?php echo $text_disabled; ?></option>
          <?php } else { ?>
          <option value="1"><?php echo $text_enabled; ?></option>
          <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
          <?php } ?>
                  </select>

                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-check_orders"><span data-toggle="tooltip" title="<?php echo $help_check_orders; ?>"><?php echo $entry_check_orders; ?></span></label>
                <div class="col-sm-10">
                  <select name="module_antispambycleantalk_check_orders" id="antispambycleantalk-check_orders" class="form-control">
          <?php if ($module_antispambycleantalk_check_orders) { ?>
          <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
          <option value="0"><?php echo $text_disabled; ?></option>
          <?php } else { ?>
          <option value="1"><?php echo $text_enabled; ?></option>
          <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
          <?php } ?>
                  </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-check_contact_form"><span data-toggle="tooltip" title="<?php echo $help_check_contact_form; ?>"><?php echo $entry_check_contact_form; ?></span></label>
                <div class="col-sm-10">
                  <select name="module_antispambycleantalk_check_contact_form" id="antispambycleantalk-check_contact_form" class="form-control">
          <?php if ($module_antispambycleantalk_check_contact_form) { ?>
          <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
          <option value="0"><?php echo $text_disabled; ?></option>
          <?php } else { ?>
          <option value="1"><?php echo $text_enabled; ?></option>
          <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
          <?php } ?>
                  </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-check_reviews"><span data-toggle="tooltip" title="<?php echo $help_check_reviews; ?>"><?php echo $entry_check_reviews; ?></span></label>
                <div class="col-sm-10">
                  <select name="module_antispambycleantalk_check_reviews" id="antispambycleantalk-check_reviews" class="form-control">
          <?php if ($module_antispambycleantalk_check_reviews) { ?>
          <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
          <option value="0"><?php echo $text_disabled; ?></option>
          <?php } else { ?>
          <option value="1"><?php echo $text_enabled; ?></option>
          <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
          <?php } ?>
                  </select>
                </div>
            </div> 
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-enable_sfw"><span data-toggle="tooltip" title="<?php echo $help_enable_sfw; ?>"><?php echo $entry_enable_sfw; ?></span></label>
                <div class="col-sm-10">
                  <select name="module_antispambycleantalk_enable_sfw" id="antispambycleantalk-enable_sfw" class="form-control">
          <?php if ($module_antispambycleantalk_enable_sfw) { ?>
          <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
          <option value="0"><?php echo $text_disabled; ?></option>
          <?php } else { ?>
          <option value="1"><?php echo $text_enabled; ?></option>
          <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
          <?php } ?>
                  </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-enable_sfw"><span data-toggle="tooltip" title="<?php echo $help_enable_sfw; ?>"><?php echo $entry_enable_sfw; ?></span></label>
                <div class="col-sm-10">
                    <select name="module_antispambycleantalk_enable_sfw" id="antispambycleantalk-enable_sfw" class="form-control">
                        <?php if ($module_antispambycleantalk_enable_sfw) { ?>
                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                        <option value="0"><?php echo $text_disabled; ?></option>
                        <?php } else { ?>
                        <option value="1"><?php echo $text_enabled; ?></option>
                        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="antispambycleantalk-access_key"><span data-toggle="tooltip" title="<?php echo $help_access_key; ?>"><?php echo $entry_access_key; ?></span></label>
                <div class="col-sm-10">
                    <input type="text" name="module_antispambycleantalk_access_key" value="<?php echo $module_antispambycleantalk_access_key; ?>" placeholder="<?php echo $entry_access_key; ?>" id="antispambycleantalk-access_key" class="form-control" />
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
<style type="text/css">
</style>
{{ footer }}