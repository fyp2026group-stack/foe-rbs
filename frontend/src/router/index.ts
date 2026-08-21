import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import Master_Admin_Dashboard from '../views/MasterAdmin/Master_Admin_Dashboard.vue';
import Resources from '../views/MasterAdmin/Resources.vue';
import Booking from '../views/MasterAdmin/Booking.vue';
import Users from '../views/MasterAdmin/Users.vue';
import Reports from '../views/MasterAdmin/Reports.vue';
import Setting from '../views/MasterAdmin/Setting.vue';
import Categories from '../views/MasterAdmin/Categories.vue';
import Templates from '../views/MasterAdmin/Templates.vue';
import Add_Resource from '../views/MasterAdmin/Add_Resource.vue';
import Use_Template from '../views/MasterAdmin/Use_Template.vue';
import Single_Resource from '../views/MasterAdmin/Single_Resource.vue';
import Single_Resource_Booking from '../views/MasterAdmin/Single_Resource_Booking.vue';
import Booking_Item from '../views/MasterAdmin/Booking_Item.vue';
import User_Dashboard from '../views/User/User_Dashboard.vue';
import Admin_Dashboard from '../views/Admin/Admin_Dashboard.vue';
import Department from '../views/MasterAdmin/Department.vue';


import User_Resource from '../views/User/Resource.vue';
import User_Booking from '../views/User/Booking.vue';
import User_Setting from '../views/User/Setting.vue';


import Admin_Booking from '../views/Admin/Booking.vue';
import Admin_Reports from '../views/Admin/Reports.vue';
import Admin_Setting from '../views/Admin/Setting.vue';
import Admin_Users from '../views/Admin/Users.vue';
import Resource from '../views/Admin/Resource.vue';
import Add_resource from '../views/Admin/Add_resource.vue';
import Use_template from '../views/Admin/Use_template.vue';
import Single_resource from '../views/Admin/Single_resource.vue';
import Single_resource_booking from '../views/Admin/Single_resource_booking.vue';


import User_Single_Resource from '../views/User/Single_Resource.vue';
import User_Single_Resource_Booking from '../views/User/Single_Resource_Booking.vue';
import PublicBookings from '../views/Guest/PublicBookings.vue';
import GuestResourceGallery from '../views/Guest/GuestResourceGallery.vue';
import GuestBooking from '../views/Guest/GuestBooking.vue';
import GuestSingleView from '../views/Guest/GuestSingleView.vue';




const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },

  {
    path:'/',
    redirect: '/register',
    
  },
  {
    path:'/register',
    name: 'Register',
    component: Register
  },
  
  {
    path:'/',
    redirect: '/forgot-password',
    
  },
  {
    path:'/forgot-password',
    name: 'forgot-password',
    component: ForgotPassword
  },
 
  {
    path:'/master-admin/categories',
    name:'master-admin-categories',
    component:Categories,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  {
   path: '/master-admin/booking_item',
   name: 'master-admin-booking-item',
   component:Booking_Item,
   meta: {requiresAuth: true, role: 'Master Admin'}
  },

  {
   path: '/master-admin/department',
   name: 'master-admin-department',
   component:Department,
   meta: {requiresAuth: true, role: 'Master Admin'}
  },
 
  {
    path:'/master-admin/templates',
    name:'master-admin-templates',
    component:Templates,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

 
  {
    path: '/master-admin/resource',
    name: 'master-admin-resource',
    component:Resources,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },


  {
    path:'/master-admin/add-resource',
    name:'master-admin-add-resource',
    component:Add_Resource,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  {
    path:'/master-admin/resource/:id',
    name:'master-admin-Single-Resource',
    component:Single_Resource,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  {
    path:'/master-admin/single-resource-booking',
    name:'master-admin-single-resource-booking',
    component:Single_Resource_Booking,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  {
    path:'/master-admin/use-template',
    name:'master-admin-use-template',
    component:Use_Template,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  
  {
    path: '/master-admin/booking',
    name: 'master-admin-booking',
    component:Booking,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  
  {
    path: '/master-admin/users',
    name: 'master-admin-users',
    component:Users,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

 
 
 
  {
    path:'/master-admin/setting',
    name:'master-admin-setting',
    component:Setting,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },
  {
    path: '/master-admin/dashboard',
    name: 'master-admin-dashboard',
    component: Master_Admin_Dashboard,
    meta: { requiresAuth: true, role: 'Master Admin' }
  },
   {
    path: '/master-admin/reports',
    name: 'master-admin-reports',
    component:Reports,
    meta: {requiresAuth: true, role: 'Master Admin'}
  },

  {
    path: '/user/dashboard',
    name: 'user-dashboard',
    component: User_Dashboard,
    meta: { requiresAuth: true, role: ['User'] }
  },
  {
    path: '/user/resource',
    name: 'user-resource',
    component: User_Resource,
    meta: { requiresAuth: true, role: ['User'] }
  },
  {
    path: '/user/booking',
    name: 'user-booking',
    component: User_Booking,
    meta: { requiresAuth: true, role: ['User'] }
  },
  {
    path: '/user/setting',
    name: 'user-setting',
    component: User_Setting,
    meta: { requiresAuth: true, role: ['User'] }
  },
  {
    path: '/public-bookings',
    name: 'public-bookings',
    component: PublicBookings,
    meta: { requiresAuth: true, role: ['Guest'] }
  },
  {
    path: '/guest-resources',
    name: 'guest-resources',
    component: GuestResourceGallery,
    meta: { requiresAuth: true, role: ['Guest'] }
  },
  {
    path: '/guest-resources/:id',
    name: 'guest-single-resource',
    component: GuestSingleView,
    meta: { requiresAuth: true, role: ['Guest'] }
  },
  {
    path: '/guest-resources/:id/book',
    name: 'guest-booking',
    component: GuestBooking,
    meta: { requiresAuth: true, role: ['Guest'] }
  },

   {
    path:'/user/resource/:id',
    name: 'user-single-resource',
    component: User_Single_Resource,
    meta: { requiresAuth: true, role: ['User', 'Guest'] }
  },

  {
    path:'/user/single-resource-booking',
    name: 'user-single-resource-booking',
    component: User_Single_Resource_Booking,
    meta: { requiresAuth: true, role: ['User', 'Guest'] }
  },

   {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: Admin_Dashboard,
    meta: { requiresAuth: true, role: 'Admin' }
  },
     {
    path: '/admin/resource',
    name: 'admin-resource',
    component: Resource,
    meta: { requiresAuth: true, role: 'Admin', permission: 'manage_resources' }
  },
   {
    path: '/admin/booking',
    name: 'admin-booking',
    component: Admin_Booking,
    meta: { requiresAuth: true, role: 'Admin', permission: 'manage_bookings' }
  },
   {
    path: '/admin/reports',
    name: 'admin-reports',
    component: Admin_Reports,
    meta: { requiresAuth: true, role: 'Admin', permission: 'view_reports' }
  },
   {
    path: '/admin/setting',
    name: 'admin-setting',
    component: Admin_Setting,
    meta: { requiresAuth: true, role: 'Admin' }
  },
    {
    path: '/admin/users',
    name: 'admin-user',
    component: Admin_Users,
    meta: { requiresAuth: true, role: 'Admin', permission: 'manage_users' }
  },
   {
    path: '/admin/add-resource',
    name: 'admin-add-resource',
    component: Add_resource,
    meta: { requiresAuth: true, role: 'Admin' }
  },
   {
    path: '/admin/use-template',
    name: 'admin-use-template',
    component: Use_template,
    meta: { requiresAuth: true, role: 'Admin' }
  },
   {
    path: '/admin/resource/:id',
    name: 'admin-single-resource',
    component: Single_resource,
    meta: { requiresAuth: true, role: 'Admin' }
  },
   {
    path: '/admin/single-resource-booking',
    name: 'admin-single-resource-booking',
    component: Single_resource_booking,
    meta: { requiresAuth: true, role: 'Admin' }
  }

];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, _from, next) => {
  const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
  const userRole = localStorage.getItem('userRole');
  let userPermissions: string[] = [];
  try {
    userPermissions = JSON.parse(localStorage.getItem('userPermissions') || '[]');
  } catch (e) {
    userPermissions = [];
  }

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login');
  } else if (to.meta.role) {
    const roles = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role];
    if (!roles.includes(userRole)) {
      next('/login');
    } else {
      // Role is valid, check permission if specified
      if (to.meta.permission) {
        if (!userPermissions.includes(to.meta.permission as string) && !userPermissions.includes('*')) {
          // If no permission, redirect to their dashboard
          if (userRole === 'Admin') next('/admin/dashboard');
          else if (userRole === 'Master Admin') next('/master-admin/dashboard');
          else if (userRole === 'User') next('/user/dashboard');
          else next('/login');
        } else {
          next();
        }
      } else {
        next();
      }
    }
  } else {
    next();
  }
});

export default router;
