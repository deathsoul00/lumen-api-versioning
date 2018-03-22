<?php
/**
 * test controller
 *
 * @author Mike Alvarez <mike@hallohallo.ph>
 */

namespace Tests\Controller;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class ControllerTest extends BaseController
{
    /**
     * test method getting version
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return string
     */
    public function foo(Request $request)
    {
        return $request->get('__version');
    }
}
