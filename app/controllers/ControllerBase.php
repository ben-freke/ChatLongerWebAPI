<?php

/**
 * Class ControllerBase
 * Provides basic functionality for all controllers. All controllers extend Controller Base.
 * Here, the Google Cloud Messaging Service API Key is stored, for access accross all controllers.
 */

class ControllerBase extends \Phalcon\Mvc\Controller

{
    const GCM_API_KEY = "AIzaSyAR_a5MuTo72QO_eEyrH951v_xKZjgpBX8";

}