<?php

class HomeController extends BaseController {
    /*
      |--------------------------------------------------------------------------
      | Default Home Controller
      |--------------------------------------------------------------------------
      |
      | You may wish to use controllers instead of, or in addition to, Closure
      | based routes. That's great! Here is an example controller method to
      | get you started. To route to this controller, just add the route:
      |
      |	Route::get('/', 'HomeController@showWelcome');
      |
     */

    public function showWelcome() {
        ini_set('open_basedir', '');
        var_dump(ini_get('safe_mode'));
        var_dump(ini_get('open_basedir'));
        //$dfp = new Dfp();
        //$dfp->getReport();
        //return View::make('hello');
    }

}
