{{-- This file is used to store sidebar items, inside the Backpack admin panel --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting') }}'><i class='nav-icon la la-cog'></i> <span>Settings</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('users') }}"><i class="nav-icon la la-question"></i> Users</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('company') }}"><i class="nav-icon la la-question"></i> Companies</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('positions') }}"><i class="nav-icon la la-question"></i> Positions</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('roles') }}"><i class="nav-icon la la-question"></i> Roles</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('permissions') }}"><i class="nav-icon la la-question"></i> Permissions</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('duties') }}"><i class="nav-icon la la-question"></i> Duties</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('employees') }}"><i class="nav-icon la la-question"></i> Employees</a></li>