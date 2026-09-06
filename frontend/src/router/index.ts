import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import OrderView from '../views/OrderView.vue'
import AdminOrdersView from '../views/AdminOrdersView.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView,
        },
        {
            path: '/orders/:id',
            name: 'order',
            component: OrderView,
        },
        {
            path: '/admin/orders',
            name: 'admin-orders',
            component: AdminOrdersView,
        },
    ],
})

export default router