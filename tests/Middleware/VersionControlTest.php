<?php
/**
 * test case for VersionControl middleware class
 *
 * @author Mike Alvarez <mike@hallohallo.ph>
 */

namespace Tests\Middleware;

use App\Http\Middleware\VersionControl as VersionControlMiddleware;
use App\Libraries\Versioning\VersionControl as VersionControlProcessor;
use App\Libraries\Versioning\VersionControlInterface;
use Tests\TestCase;
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
        $this->app->bind(VersionControlInterface::class, function ($app) {
            return $this->app->make(VersionControlProcessor::class);
        });
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
     * data provider
     *
     * @return array
     */
    public function acceptVersions()
    {
        return [
            ['1', '1'],
            ['2.1', '2.1'],
            ['3.1', '1'],
            ['1a', '1a'],
            ['2a', '2a'],
            ['1.0a', '1.0a'],
            ['1.1a', '1.1a'],
        ];
    }

    /**
     * test handle middleware case
     *
     * @dataProvider versionResources
     *
     * @return void
     */
    public function testMiddlewareHandle($version, $routeInfo, $expectedRouteInfo)
    {
        $request = Request::create('http://example.com', 'GET');
        $request->headers->set('Accept', "application/vnd.api-v{$version}+json");
        $request->setRouteResolver(function () use ($routeInfo) {
            return $routeInfo;
        });

        $this->routeInfo = [];

        $closure = function ($request) {
            $this->routeInfo = $request->route();
        };

        if ($version == '3.1') {
            $this->expectException(HttpResponseException::class);
        }

        $dependency = $this->app->make(VersionControlProcessor::class);
        $middleware = new VersionControlMiddleware($dependency);

        // handle request
        $middleware->handle($request, $closure);

        // assert
        if ($version != '3.1') {
            $this->assertNotEmpty($request->get('__version'));
            $this->assertEquals($expectedRouteInfo, $this->routeInfo);
        }
    }

    /**
     * test middleware register on application route
     *
     * @dataProvider acceptVersions
     *
     * @return void
     */
    public function testMiddlewareRegisterJsonWithPlaceholder($version, $expectedVersion)
    {
        $this->app->get(
            '/foo',
            [
                'uses' => 'Tests\Controller\V1\ControllerTest@foo',
                'middleware' => VersionControlMiddleware::class,
            ]
        );

        $response = $this->call(
            'GET',
            '/foo',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => "application/vnd.api-v{$version}+json"
            ]
        );

        if ($version == '3.1') {
            $this->assertResponseStatus(400);
            $this->receiveJson();
        } else {
            $this->assertResponseOk();
            $this->assertEquals($expectedVersion, $response->getContent());
        }
    }

    /**
     * test middleware register on application route
     *
     * @dataProvider acceptVersions
     *
     * @return void
     */
    public function testMiddlewareRegisterJsonWithoutPlaceholder($version, $expectedVersion)
    {
        $this->app->get(
            '/foo',
            [
                'uses' => 'Tests\Controller\V1\ControllerTest@foo',
                'middleware' => VersionControlMiddleware::class,
            ]
        );

        $response = $this->call(
            'GET',
            '/foo',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => "application/vnd.api-v{$version}+json"
            ]
        );

        if ($version == '3.1') {
            $this->assertResponseStatus(400);
            $this->receiveJson();
        } else {
            $this->assertResponseOk();
            $this->assertEquals($expectedVersion, $response->getContent());
        }
    }
}
