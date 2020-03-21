<?php
namespace Stanford\PRDC;
/** @var PRDC $this */

/**
 *  Insert our modal that will collect the desired value
 */

// Hide the default REDCap page for now on load - using CSS so it isn't jumpy
?>
    <style type="text/css">
        #container {display:none;}
        #search-spinner {opacity: 0;}
    </style>
<?php

// Insert javascript
?>
    <script type="text/javascript">
        var PRDC = {
            lookupUrl: <?php echo json_encode($this->getUrl("ajax.php",true,true)) ?>
        }
    </script>

    <script type="text/javascript" src="<?php echo $this->getUrl("assets/PRDC.js",true,true) ?>"></script>

<?php

// Insert the modal
?>
    <div id="PRDC" class="container">
        <div class="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo $this->lookup_title ?></h5>
                    </div>
                    <div class="modal-body">
                        <?php echo $this->lookup_header ?>
                        <p>
                            <div class="input-group mb-3">
                                <input style="height: 40px !important; font-size: 20px !important;" type="text" class="form-control input-lg text-center" placeholder="Search">
                                <div class="input-group-append">
                                    <button class="btn btn-success" name="search">
                                        <div id="search-spinner" class="spinner-border spinner-border-sm mb-1 mr-1" role="status"></div>
                                        Search
                                    </button>
                                </div>
                            </div>
                        </p>
                        <div class="lookup-result text-center p-2">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-cancel">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
