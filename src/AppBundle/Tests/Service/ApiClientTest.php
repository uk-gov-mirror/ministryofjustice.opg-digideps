<?php
namespace AppBundle\Tests\Service;

//use AppBundle\Service\ApiClient;
use Mockery as m;
use AppBundle\Entity as EntityDir;


class ApiClientTest extends \PHPUnit_Framework_TestCase
{
    private $jsonSerializer;
    private $apiClientMock;
    
    public function setUp()
    {
        $this->jsonSerializer = m::mock('JMS\Serializer\Serializer');
        
        $options = [ 'base_url' => 'https://digideps.api/',
                     'endpoints' => [ 'find_user_by_email' => 'find-user-by-email'],
                     'format' => 'json',
                     'debug' => null ];
        //mock api client 
        $this->apiClientMock = m::mock('AppBundle\Service\ApiClient[get,post,put]', [ $this->jsonSerializer, $options ]);
    }
    
    public function tearDown()
    {
        m::close();
    }
    
    /**
     * Test get entity
     */
    public function testGetEntity()
    {
        $user = [ 'data' => [ 'id' => 1,
                              'firstname' => 'John',
                              'lastname' => 'Doe',
                              'email' => 'john.doe@digital.justice.gov.uk',
                              'password' => 'nothingsecret',
                              'active' => true,
                              'role' => 'ROLE_ADMIN',
                            ]];
        
        $userObject = new EntityDir\User();
        
        $this->jsonSerializer->shouldReceive('deserialize')->times(2)->with(m::any(),m::any(),m::any())->andReturn($user,$userObject);
       
        $mockGuzzleResponse = m::mock('Guzzle\Http\Message\Response');
        
        $mockGuzzleResponse->shouldReceive(['getBody' => json_encode($user)])->times(1);
        
        $this->apiClientMock->shouldReceive('get')->times(1)->andReturn($mockGuzzleResponse);
        
        $this->assertInstanceOf('\AppBundle\Entity\User',$this->apiClientMock->getEntity('User', 'find_user_by_email'));
    }
    
    /**
     * test get entities
     */
    public function testGetEntities()
    {
        $users = [ 'data' => [ [ 'id' => 1,
                                 'firstname' => 'John',
                                 'lastname' => 'Doe',
                                 'email' => 'john.doe@digital.justice.gov.uk',
                                 'password' => 'nothingsecret',
                                 'active' => true,
                                 'role' => 'ROLE_ADMIN' ],
           
                                [ 'id' => 1,
                                  'firstname' => 'Nolan',
                                  'lastname' => 'Ross',
                                  'email' => 'nolan.ross@digital.justice.gov.uk',
                                  'password' => 'nothingsecret',
                                  'active' => true,
                                  'role' => 'ROLE_ADMIN' ] 
                             ] 
               ];
       $userObject = new EntityDir\User();
       
       $this->jsonSerializer->shouldReceive('deserialize')->times(3)->with(m::any(),m::any(),m::any())->andReturn($users,$userObject, $userObject);
       
       $mockGuzzleResponse = m::mock('Guzzle\Http\Message\Response');
       
       $mockGuzzleResponse->shouldReceive(['getBody' => json_encode($users)])->times(1);
        
       $this->apiClientMock->shouldReceive('get')->times(1)->andReturn($mockGuzzleResponse);
        
       $this->assertInternalType('array',$this->apiClientMock->getEntities('User', 'find_user_by_email'));
    }
    
    /**
     * test postC
     */
    public function testPostCWhenBodyEntityIsObject()
    {
        $user = [ 'id' => 1,
                  'firstname' => 'John',
                  'lastname' => 'Doe',
                  'email' => 'john.doe@digital.justice.gov.uk',
                  'password' => 'nothingsecret',
                  'active' => true,
                  'role' => 'ROLE_ADMIN' ];
        
        $userObject = new EntityDir\User();
        
        $this->jsonSerializer->shouldReceive('serialize')->times(1)->with(m::any(),m::any(), m::any())->andReturn(json_encode($user));
        $this->jsonSerializer->shouldReceive('deserialize')->times(1)->with(m::any(),m::any(), m::any())->andReturn(['data' => $user]);
        
        $mockGuzzleResponse = m::mock('Guzzle\Http\Message\Response');
        $mockGuzzleResponse->shouldReceive(['getBody' => json_encode([ 'data' => json_encode($user)]) ])->times(1);
        
        $this->apiClientMock->shouldReceive('post')->with(m::any(),m::any())->times(1)->andReturn($mockGuzzleResponse);
        
        $this->assertInternalType('array', $this->apiClientMock->postC('find_user_by_email', $userObject));
    }
    
    
    /**
     * test putC
     */
    public function testPutCWhenBodyEntityIsObject()
    {
        $user = [ 'id' => 1,
                  'firstname' => 'John',
                  'lastname' => 'Doe',
                  'email' => 'john.doe@digital.justice.gov.uk',
                  'password' => 'nothingsecret',
                  'active' => true,
                  'role' => 'ROLE_ADMIN' ];
        
        $userObject = new EntityDir\User();
        
        $this->jsonSerializer->shouldReceive('serialize')->times(1)->with(m::any(),m::any(), m::any())->andReturn(json_encode($user));
        $this->jsonSerializer->shouldReceive('deserialize')->times(1)->with(m::any(),m::any(), m::any())->andReturn(['data' => $user]);
        
        $mockGuzzleResponse = m::mock('Guzzle\Http\Message\Response');
        $mockGuzzleResponse->shouldReceive(['getBody' => json_encode([ 'data' => json_encode($user)]) ])->times(1);
        
        $this->apiClientMock->shouldReceive('put')->with(m::any(),m::any())->times(1)->andReturn($mockGuzzleResponse);
        
        $this->assertInternalType('array', $this->apiClientMock->putC('find_user_by_email', $userObject));
    }
    
    
    /*public function testCreateRequest()
    {
        $this->assertInstanceOf('GuzzleHttp\Message\Request', $this->apiClientMock->createRequest('GET','find_user_by_email', [ 'query' => [ 'test']]));
    }*/
}