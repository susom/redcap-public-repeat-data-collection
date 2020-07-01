<?php /** @noinspection ALL */
namespace Stanford\PRDC;
/** @var PRDC $module */

use Exception;

// Handle a Lookup
if (!empty($_POST['search'])) {

    $search = filter_var($_POST['search'],FILTER_SANITIZE_STRING);

    // Does this record already exist
    $record = $module->lookupRecord($search);
    $module->emDebug("REcord: $record");

    // Set default response as not-found
    $result = array(
        "result" => "not-found",
        "comment" => "$module->lookup_field = '$search' was not found within REDCap database.<br>Try again or create a new record.",
        "field" => $module->lookup_field,
        "valid" => null
    );

    if ($record === false) {
        // Record was not found

        $result['buttonAction'] = "close";
        $result['buttonText'] = "<i class='fas fa-plus-circle'></i> Create New Record for '$search'";
        $result['btnClass'] = "btn-success";

        // Lets check if MRN validation is on?
        if ($module->validate_mrn) {
            // Let's try to see if $search is a valid MRN
            // is the mrnlookup module enabled on this project?
            // if so, try and use it to get verify the mrn is present in the emr?

            // Default the result array to not found
            $result = array(
                "result" => "not-found",
                "field" => $module->lookup_field,
                "valid" => false
            );

            try{
                // See if we can instantiate the MRN Lookup module and if so check the IRB
                $mrn_lookup = \ExternalModules\ExternalModules::getModuleInstance('mrn_lookup');
                $return_irb = $mrn_lookup->checkIRBAndGetAttestation($module->getProjectId());
                if ($return_irb["status"]) {

                    // IRB is valid so retrieve a token for the ID API
                    $return_token = $mrn_lookup->retrieveIdToken();
                    if ($return_token["status"]) {

                        // Valid token was found so make the request to verify the MRN
                        $return_api = $mrn_lookup->apiPost($pid, $search, $return_token["token"], $return_token["url"]);
                        if ($return_api["status"]) {

                            // If the person object is not empty, this MRN was found
                            if (empty($return_api["person"])) {
                                $result = array(
                                    "valid" => false,
                                    "result" => "not-found",
                                    "comment" => "$module->lookup_field = '$search' was not found in STARR. <br>Try again or Cancel to create a new record."
                                );

                            } else {

                                // Person not found in the EMR
                                $result = array(
                                    //"valid" => true,
                                    //"result" => "not-found",
                                    "field" => $module->lookup_field,
                                    "buttonAction" => "close",
                                    "comment" => "$module->lookup_field = '$search' was found in STARR",
                                    "buttonText" => "<i class='fas fa-plus-circle'></i> Create New Record for '$search'",
                                    "btnClass" => "btn-success"
                                );
                            }
                        } else {
                            $result["comment"] = $return_api["message"];
                        }
                    } else {
                        $result["comment"] = $return_token["message"];
                    }
                } else {
                    $result["comment"] = $return_irb["message"];
                }
            } catch (\Exception $ex) {
                $error = "MRN LookUp cannot be instantiated from public-repeat-data-collection";
                $module->emError($error);
                $result["comment"] = $error;
            }
        }
    } else {

        // What is the action to do if the record was found?
        $result['result'] = "found";
        $result['comment'] = "$module->lookup_field = '$search' was found within REDCap database";
        $result['buttonAction'] = "redirect";

        if ($module->found_action == 1) {
            // Get the next repeating survey

            // Create helper object
            $rf = new RepeatingForms($module->getProjectId(),$module->repeating_form_name);

            // Get the next instance
            $next_instance = $rf->getNextInstanceId($record, $module->repeating_event_id);

            $module->emDebug($rf->last_error_message, $record, $next_instance);

            // Get the survey url for that instance
            $result['buttonUrl'] = $rf->getSurveyUrl($record,$next_instance);
            $result['buttonText'] = "<i class='fas fa-angle-double-right'></i> Add Instance to Existing Record";
        } elseif ($module->found_action == 2) {
            // Get the survey queue url
            $result['buttonUrl'] = \REDCap::getSurveyQueueLink($record);
            $result['buttonText'] = "View Record Survey Queue";
        }
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}

