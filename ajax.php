<?php /** @noinspection ALL */
namespace Stanford\PRDC;
/** @var PRDC $module */

// Handle a Lookup
if (!empty($_POST['search'])) {

    $search = filter_var($_POST['search'],FILTER_SANITIZE_STRING);

    // Does this record already exist
    $record = $module->lookupRecord($search);

    // Set default response as not-found
    $result = array(
        "result" => "not-found",
        "comment" => $module->lookup_field . " = <b>$search</b> was not found in the REDCap database.<br>Try again or create a new record.",
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
            // if so, tgry and use it to get verify the mrn is present in the emr?
            // Currently not supported
            if ($valid) {
                // $result['valid'] = true;
            } else {
                // $result['valid'] = false;
            }
        }
    } else {

        // What is the action to do if the record was found?
        $result['result'] = "found";
        $result['comment'] = "Record $record was found with $module->lookup_field = '$search'";
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

