<?php

class CreateEcommerceMemberGroups extends BuildTask{

	protected $title = "Create E-commerce Member Groups";

	protected $description = "Create the member groups and members for e-commerce.";

	function run($request){
		$customerGroup = EcommerceRole::get_customer_group();
		if(!$customerGroup) {
			$customerGroup = new Group();
			$customerGroup->Code = EcommerceRole::get_customer_group_code();
			$customerGroup->Title = EcommerceRole::get_customer_group_name();
			$customerGroup->write();
			Permission::grant( $customerGroup->ID, EcommerceRole::get_customer_permission_code());
			DB::alteration_message(EcommerceRole::get_customer_group_name().' Group created',"created");
		}
		elseif(DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '".$customerGroup->ID."' AND \"Code\" LIKE '".EcommerceRole::get_customer_permission_code()."'")->numRecords() == 0 ) {
			Permission::grant($customerGroup->ID, EcommerceRole::get_customer_permission_code());
			DB::alteration_message(EcommerceRole::get_customer_group_name().' permissions granted',"created");
		}
		if(!$customerGroup = EcommerceRole::get_customer_group()) {
			die("ERROR");
		}
		else {
			DB::alteration_message(EcommerceRole::get_customer_group_name().' is ready for use',"created");
		}
		$adminGroup = EcommerceRole::get_admin_group();
		if(!$adminGroup) {
			$adminGroup = new Group();
			$adminGroup->Code = EcommerceRole::get_admin_group_code();
			$adminGroup->Title = EcommerceRole::get_admin_group_name();
			$adminGroup->write();
			Permission::grant( $adminGroup->ID, EcommerceRole::get_admin_permission_code());
			DB::alteration_message(EcommerceRole::get_admin_group_name().' Group created',"created");
		}
		elseif(DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '".$adminGroup->ID."' AND \"Code\" LIKE '".EcommerceRole::get_admin_permission_code()."'")->numRecords() == 0 ) {
			Permission::grant($adminGroup->ID, EcommerceRole::get_admin_permission_code());
			DB::alteration_message(EcommerceRole::get_admin_group_name().' permissions granted',"created");
		}
		$permissionRole = DataObject::get_one("PermissionRole", "\"Title\" = '".EcommerceRole::get_admin_role_title()."'");
		if(!$permissionRole) {
			$permissionRole = new PermissionRole();
			$permissionRole->Title = EcommerceRole::get_admin_role_title();
			$permissionRole->OnlyAdminCanApply = true;
			$permissionRole->write();
		}
		if($permissionRole) {
			$permissionArray = EcommerceRole::get_admin_role_permission_codes();
			if(is_array($permissionArray) && count($permissionArray) && $permissionRole) {
				foreach($permissionArray as $permissionCode) {
					$permissionRoleCode = DataObject::get_one("PermissionRoleCode", "\"Code\" = '$permissionCode'");
					if(!$permissionRoleCode) {
						$permissionRoleCode = new PermissionRoleCode();
						$permissionRoleCode->Code = $permissionCode;
						$permissionRoleCode->RoleID = $permissionRole->ID;
						$permissionRoleCode->write();
					}
				}
			}
			if($adminGroup) {
				$existingGroups = $permissionRole->Groups();
				$existingGroups->add($adminGroup);
			}
		}
	}

}
