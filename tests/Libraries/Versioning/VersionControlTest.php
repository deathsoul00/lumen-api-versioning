<?php
/**
 * test class for \App\Libraries\Versioning\VersionControl class
 *
 * @author Mike Alvarez <mike@hallohallo.ph>
 */

namespace Tests\Libraries\Versioning;

use Tests\TestCase;
use App\Libraries\Versioning\VersionControl;
use App\Libraries\Versioning\VersionControlInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class VersionControlTest extends TestCase
{
    /**
     * setup
     */
    public function setUp()
    {
        parent::setUp();

        // Register App\Libraries\Versioning\VersionControlInterface::class
        $this->versionControl = $this->app->make(VersionControl::class);
    }

    /**
     * test version control class instance
     *
     * @return void
     */
    public function testInstance()
    {
        $this->assertInstanceOf(VersionControlInterface::class, $this->versionControl);
    }

    /**
     * data provider
     *
     * @return array
     */
    public function versionResources()
    {
        return [
            [
                '1.1',
                [true, ['uses' => 'Tests\Controller\V{d}\ControllerTest@foo']],
                [true, ['uses' => 'Tests\Controller\V1\ControllerTest@foo']]
            ],
            [
                '2.1',
                [true, ['uses' => 'Tests\Controller\V{d}\ControllerTest@foo']],
                [true, ['uses' => 'Tests\Controller\V2\ControllerTest@foo']]
            ],
            [
                '3.1',
                [true, ['uses' => 'Tests\Controller\V{d}\ControllerTest@foo']],
                [true, ['uses' => 'Tests\Controller\V1\ControllerTest@foo']]
            ],
        ];
    }

    /**
     * test version processing
     *
     * @dataProvider versionResources
     *
     * @return void
     */
    public function testProcessVersionNamespace($version, $routeInfo, $expectedRouteInfo)
    {
        $request = Request::create('http://example.com', 'GET');
        $request->headers->set('Accept', "application/vnd.api-v{$version}+json");
        $request->setRouteResolver(function () use ($routeInfo) {
            return $routeInfo;
        });

        // assert exception
        if ($version == '3.1') {
            $this->expectException(HttpResponseException::class);
        }

        $this->versionControl->processRequestVersioning($request);

        // get processed route info
        $routeInfo = call_user_func($request->getRouteResolver());

        // assert
        if ($version != '3.1') {
            $this->assertNotEmpty($request->get('__version'));
            $this->assertEquals($expectedRouteInfo, $routeInfo);
        }
    }

    /**
     * test version processing
     *
     * @dataProvider versionResources
     *
     * @return void
     */
    public function testProcessVersionNamespaceFromRequestInjection($version, $routeInfo, $expectedRouteInfo)
    {
        $request = $this->app->make('request');
        $request->headers->set('Accept', "application/vnd.api-v{$version}+json");
        $request->setRouteResolver(function () use ($routeInfo) {
            return $routeInfo;
        });

        // assert exception
        if ($version == '3.1') {
            $this->expectException(HttpResponseException::class);
        }

        // rebind \Illuminate\Http\Request
        $this->app->bind(Request::class, function ($app) use ($request) {
            return $request;
        });

        $versionControl = $this->app->make(VersionControl::class);
        $versionControl->processRequestVersioning();

        // get processed route info
        $routeInfo = call_user_func($request->getRouteResolver());

        // assert
        if ($version != '3.1') {
            $this->assertNotEmpty($request->get('__version'));
            $this->assertEquals($expectedRouteInfo, $routeInfo);
        }
    }

    /**
     * test case for VersionControl::setFallbackVersion
     *
     * @return void
     */
    public function testSetFallbackVersion()
    {
        $fallbackVersion = 2;
        $this->versionControl->setFallbackVersion($fallbackVersion);
        $this->assertEquals($fallbackVersion, $this->versionControl->getFallbackVersion());
    }

    /**
     * test throw exception of VersionControl::setFallbackVersion
     *
     * @return void
     */
    public function testSetFallbackVersionException()
    {
        $fallbackVersion = '1.1';
        $this->expectException(\InvalidArgumentException::class);
        $this->versionControl->setFallbackVersion($fallbackVersion);
    }

    /**
    /**
     * test case for VersionControl::setAcceptHeaderPattern
     *
     * @return void
     */
    public function testSetVersionPattern()
    {
        $pattern = 'application/vnd.foo.api-v[\d+]';
        $this->versionControl->setAcceptHeaderPattern($pattern);
        $this->assertEquals($pattern, $this->versionControl->getAcceptHeaderPattern());
    }

    /**
     * test throw exception of VersionControl::setAcceptHeaderPattern
     *
     * @return void
     */
    public function testSetVersionPatternException()
    {
        $pattern = 1;
        $this->expectException(\InvalidArgumentException::class);
        $this->versionControl->setAcceptHeaderPattern($pattern);
    }
}
