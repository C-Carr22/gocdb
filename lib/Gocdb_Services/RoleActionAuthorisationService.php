<?php

/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace org\gocdb\services;
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/RoleActionMappingService.php';
require_once __DIR__ . '/Role.php';

/**
 * Used to determine if a user can perform an action on an object by 
 * comparing the user's granted DB roles against the rules/roles defined in the 
 * role action mapping service. 
 *
 * @author David Meredith
 */
class RoleActionAuthorisationService  extends AbstractEntityService  {

    private $roleActionMappingService; 
    private $roleService; 
   
    /**
     * Create a new instance.  
     * @param org\gocdb\services\RoleActionMappingService $roleActionMappingService
     * @param org\gocdb\services\Role $roleService
     */
    public function __construct(RoleActionMappingService $roleActionMappingService, Role $roleService) {
        parent::__construct();
        $this->roleActionMappingService = $roleActionMappingService; //new RoleActionMappingService(); 
        $this->roleService = $roleService; //new Role();  
    }

   
    // In future we may need the following method if we want to introduce role-
    // action-mapping rules that do not apply to a specified target entity 
    //public function authoriseActionInProject($action, $projectName, \User $user){}
         
   
    
    /**
     * Analyse the user's roles to determine if the user can peform the 
     * specified action over the target entity? if true, return all the user's 
     * roles that grant the action, otherwise return an empty array. 
     * Note, there is no special Role for the GOCDB admin user, so calling 
     * code needs to determine this itself.  
     * <p>
     * Authorisation decisions are made by comparing the user's Roles that are
     * reachable from the target entity when ascending the domain graph with the
     * role-action-mapping rules for the relevant project(s). The relevant 
     * projects include those that are a parents/ancestors to the target entity.   
     * 
     * @param string $action The action the user wants to perform over the entity 
     * @param \OwnedEntity $targetEntity The target entity for the action 
     * @param \User $user The user who wants to peform the action on the entity 
     * @return array of {@see \Role}s that grant the action over entity. 
     * Can be an empty array if no role grants the action. 
     * @throws \LogicException If role action mappings can't be resolved.  
     */
    public function authoriseAction($action, \OwnedEntity $targetEntity, \User $user){
        //throw new \LogicException('not implemented yet'); 
        if (!is_string($action) || strlen(trim($action)) == 0) {
            throw new \LogicException('Invalid action');
        } 
        // If we limit the actions, then its hard to test using some sample 
        // roleActionMapping files. Need to decide to include this.  
//        if(!in_array($action, \Action::getAsArray())){
//            throw new \LogicException('Coding Error - Invalid action not known'); 
//        } 

        // Get a list of DB projects that are 'reachable' from the given entity 
        // by moving up the OwnedEntity hierarchy. 
        // Note, some objects don't come under the remit of a Project, e.g. 
        // ServiceGroups, so dbProjects may be empty (this is normal). 
        $dbProjects = $this->roleService->getReachableProjectsFromOwnedEntity($targetEntity); 

        // For each DB project, lookup+store the role type mappings that enable 
        // the action on the specified entity type (mappings in RoleActionMappings XML). 
        $requiredRoleTypesPerProj = array();

        if(count($dbProjects) > 0){
            foreach ($dbProjects as $dbProject) {
                //print_r('reachable project: ['.$dbProject->getName()."] \n"); 
                
                // Lookup the role type mappings for this project (['RoleTypeName'] => 'overOwnedEntity'). 
                // throws LogicException if the dbProjectName does not exist in the 
                // mapping file! (not so with action or entity-type)  
                // For example, the following role-types over the specified object 
                // types could enable 'actionX': 
                // array (
                //   ['Site Administrator'] => 'Site', 
                //   ['NGI Operations Manager'] => 'Ngi', 
                //   ['Chief Operations Officer'] => 'Project'
                // );      
                $roleTypeMappingsForProj = $this->roleActionMappingService->
                        getRoleTypeNamesThatEnableActionOnTargetObjectType(
                        $action, $targetEntity->getType(), $dbProject->getName());
               
                //print_r('roleTypeMappings for reachable project: ['.$dbProject->getName()."] \n"); 
                //print_r($roleTypeMappingsForProj); 
                
                // If there are roles that enable the action, store the roles for the project 
                if (count($roleTypeMappingsForProj) > 0) {
                    $requiredRoleTypesPerProj[/*$dbProject->getId()*/] = $roleTypeMappingsForProj;
                }
            }
        } else {
            // The entity does'nt come under a Project, e.g. the entity was 
            // Project agnostic like a ServiceGroup, therefore get the default
            // RoleActionMappings. 
            $requiredRoleTypesPerProj[] = $this->roleActionMappingService->
                    getRoleTypeNamesThatEnableActionOnTargetObjectType(
                        $action, $targetEntity->getType(), null);
            //print_r($requiredRoleTypesPerProj); // e.g. 
            // Array ( [0] => Array ( [Service Group Administrator] => ServiceGroup ) ) 
        }

        // Get all the roles occurring over and above the entity. Note, in future it may be 
        // necessary to introduce getUserRolesReachableFromEntityDESC($user, $entity) too 
        // (or getUserRolesReachableFromEntity($user, $entity) to go up and down). 
        // e.g. this would be needed IF we want to enable actions on parent objects from roles held over 
        // child objects, e.g. consider the case where users with roles over sites/ngis 
        // want to post comments on a Project's notifications-board - a user may need a role 
        // over a child object to do this. 
        $dbUserRoles = $this->roleService->getUserRolesReachableFromEntityASC($user, $targetEntity);  
        
        //   Note, don't get all the user's roles in the project (to compare
        //   with the role-action-mappings) because this would include roles that 
        //   are not linerarly reachable from the entity, e.g. when authorising an 
        //   an action on an NGI, we wouldn't want to include Roles linked to another NGI!
        //   in the same project. Therefore, doing the following would be wrong:  
        //   foreach ($dbProjects as $dbProject) {
        //      $rolesInProj = $this->roleService->getUserRolesByProject($user, $dbProject); 
        //      foreach($rolesInProj as $r){
        //           $dbUserRoles[] = $r
        //      } 
        //   }

        // Iterate the users roles and for each role determine if this role's type  
        // and ownedEntity type match a role action mapping rule. If true, then 
        // store that users's role in the grantingUserRoles array. 
        $grantingUserRoles = array();
        foreach ($dbUserRoles as $candidateUserRole) {
            //foreach ($requiredRoleTypesPerProj as $dbProjectId => $requiredRoleTypesForProjX) {
            foreach ($requiredRoleTypesPerProj as $requiredRoleTypesForProjX) {
                //print_r("candidate granting role: [".$candidateUserRole->getRoleType()->getName()."]\n"); 
                // Iterate required role types for proj X  
                foreach ($requiredRoleTypesForProjX as $grantingRoleTypeName => $overEntityType) {

                    // If user has a role with the same type name and 
                    // over the same entity type, this role grants the action  
                    if ($candidateUserRole->getRoleType()->getName() == $grantingRoleTypeName &&
                            strtoupper($candidateUserRole->getOwnedEntity()->getType()) == strtoupper($overEntityType)) {

                        if (!in_array($candidateUserRole, $grantingUserRoles)) {
                            //print_r("Adding role [".$candidateUserRole->getRoleType()->getName()."] over [".$candidateUserRole->getOwnedEntity()->getType()."]"); 
                            //e.g. Adding role [Service Group Administrator] over [servicegroup] 
                            $grantingUserRoles[] = $candidateUserRole;
                        }
                    }
                }
            }
        }
        //print_r("Granting User Roles size: [".count($grantingUserRoles)."]"); 

        return $grantingUserRoles; 
    }

    
}
