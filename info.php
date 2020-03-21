<?php
namespace Stanford\PRDC;
/** @var PRDC $module */

include_once (APP_PATH_DOCROOT . "ProjectGeneral/header.php");
require_once ("Parsedown.php");


// Do a full validation each time this page is loaded:
$module->validate();

?>
    <h3> <?php echo $module->getModuleName() ?> Configuration</h3>

    <p>
        This page displays any errors on the configuration of your Public Repeat Data Collection module
    </p>

<?php
if (empty($module->errors)) {
    // all is good
    ?>
    <span style="font-size: 60pt; color:green;"><i class="fas fa-thumbs-up"></i> Looks good!</span>
    <?php
} else {
    // show errors
    echo "<div class='alert alert-danger'>"
        ."<h5>Configuration Errors</h5>"
        ."<ol><li>"
        .implode("</li><li>",$module->errors) . "</li>"
        ."</ol></div>";
}

echo "<hr>";

$Parsedown = new Parsedown;
$Parsedown->setBaseUrl(  dirname($module->getUrl()) . DS );
echo $Parsedown->text(file_get_contents($module->getModulePath() . DS . "README.md"));




