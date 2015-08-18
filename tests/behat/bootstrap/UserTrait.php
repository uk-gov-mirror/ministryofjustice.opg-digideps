<?php

namespace DigidepsBehat;

trait UserTrait
{
    // added here for simplicity
    private static $roleNameToRoleId = ['admin'=>1, 'lay deputy'=>2];
    
    /**
     * it's assumed you are logged as an admin and you are on the admin homepage (with add user form)
     * 
     * @When I create a new :role user :firstname :lastname with email :email
     */
    public function iCreateTheUserWithEmail($role, $firstname, $lastname, $email)
    {
        $this->fillField('admin_email', $email);
        $this->fillField('admin_firstname', $firstname);
        $this->fillField('admin_lastname', $lastname);
        $roleId = self::$roleNameToRoleId[strtolower($role)];
        $this->fillField('admin_roleId', $roleId);
        $this->clickOnBehatLink('save');
        $this->theFormShouldBeValid();
        $this->assertResponseStatus(200);
    }
    
     
    /**
     * @Given I change the user :userId token to :token dated last week
     */
    public function iChangeTheUserToken($userId, $token)
    {
        $this->visitBehatLink("user/{$userId}/token/{$token}/token-date/-7days");
    }
    
}