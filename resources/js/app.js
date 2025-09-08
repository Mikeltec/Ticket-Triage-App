import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import './bootstrap'

// Import views
import TicketsView from './views/TicketsView.vue'
import DashboardView from './views/DashboardView.vue'

// Create router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/tickets'
    },
    {
      path: '/tickets',
      name: 'tickets',
      component: TicketsView
    },
    {
      path: '/tickets/:id',
      name: 'ticket-detail',
      component: TicketsView,
      props: true
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView
    }
  ]
})

// Create Vue app
const app = createApp({
  data() {
    return {
      // Global app state
      loading: false,
      darkMode: false,
      notifications: []
    }
  },
  mounted() {
    // Initialize theme from localStorage or system preference
    this.initializeTheme()
  },
  methods: {
    initializeTheme() {
      // Check if user has a saved theme preference
      const savedTheme = localStorage.getItem('theme')
      if (savedTheme) {
        this.darkMode = savedTheme === 'dark'
      } else {
        // Use system preference
        this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches
      }
      this.applyTheme()
    },
    toggleTheme() {
      this.darkMode = !this.darkMode
      localStorage.setItem('theme', this.darkMode ? 'dark' : 'light')
      this.applyTheme()
    },
    applyTheme() {
      if (this.darkMode) {
        document.documentElement.classList.add('dark-theme')
      } else {
        document.documentElement.classList.remove('dark-theme')
      }
    },
    showNotification(message, type = 'info', duration = 3000) {
      const notification = {
        id: Date.now(),
        message,
        type,
        duration
      }
      this.notifications.push(notification)
      
      // Auto remove notification
      setTimeout(() => {
        this.removeNotification(notification.id)
      }, duration)
    },
    removeNotification(id) {
      const index = this.notifications.findIndex(n => n.id === id)
      if (index > -1) {
        this.notifications.splice(index, 1)
      }
    }
  },
  template: `
    <div class="app" :class="{ 'app--dark': darkMode }">
      <!-- Navigation Header -->
      <header class="app__header">
        <nav class="nav">
          <div class="nav__brand">
            <h1 class="nav__title">Smart Ticket Triage</h1>
          </div>
          
          <div class="nav__menu">
            <router-link 
              to="/tickets" 
              class="nav__link"
              :class="{ 'nav__link--active': $route.path.startsWith('/tickets') }"
            >
              Tickets
            </router-link>
            <router-link 
              to="/dashboard" 
              class="nav__link"
              :class="{ 'nav__link--active': $route.path === '/dashboard' }"
            >
              Dashboard
            </router-link>
          </div>
          
          <div class="nav__actions">
            <button 
              @click="toggleTheme" 
              class="btn btn--ghost btn--icon"
              :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
            >
              {{ darkMode ? '☀️' : '🌙' }}
            </button>
          </div>
        </nav>
      </header>

      <!-- Main Content -->
      <main class="app__main">
        <!-- Global loading overlay -->
        <div v-if="loading" class="loading-overlay">
          <div class="loading-spinner"></div>
        </div>
        
        <!-- Router View -->
        <router-view></router-view>
      </main>

      <!-- Global Notifications -->
      <div class="notifications">
        <div 
          v-for="notification in notifications"
          :key="notification.id"
          class="notification"
          :class="'notification--' + notification.type"
          @click="removeNotification(notification.id)"
        >
          <div class="notification__content">
            {{ notification.message }}
          </div>
          <button class="notification__close">×</button>
        </div>
      </div>
    </div>
  `
})

// Use router
app.use(router)

// Mount app
app.mount('#app')