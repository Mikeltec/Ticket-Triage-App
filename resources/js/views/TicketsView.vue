<template>
  <div class="tickets-view">
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header__content">
        <h2 class="page-header__title">Support Tickets</h2>
        <p class="page-header__subtitle">
          Manage and classify support tickets
        </p>
      </div>
      <div class="page-header__actions">
        <button 
          @click="showNewTicketForm = true" 
          class="btn btn--primary"
        >
          New Ticket
        </button>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters">
      <div class="filters__search">
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Search tickets..."
          class="input input--search"
        >
      </div>
      
      <div class="filters__controls">
        <select v-model="statusFilter" @change="loadTickets" class="select">
          <option value="">All Statuses</option>
          <option v-for="(label, status) in statuses" :key="status" :value="status">
            {{ label }}
          </option>
        </select>
        
        <select v-model="categoryFilter" @change="loadTickets" class="select">
          <option value="">All Categories</option>
          <option v-for="category in categories" :key="category" :value="category">
            {{ getCategoryDisplayName(category) }}
          </option>
        </select>
        
        <button @click="exportTickets" class="btn btn--ghost">
          Export CSV
        </button>
      </div>
    </div>

    <!-- Tickets List -->
    <div class="tickets-container">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading tickets...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!tickets.length" class="empty-state">
        <div class="empty-state__icon">📝</div>
        <h3 class="empty-state__title">No tickets found</h3>
        <p class="empty-state__message">
          {{ hasFilters ? 'Try adjusting your filters' : 'Create your first support ticket' }}
        </p>
      </div>

      <!-- Tickets Grid -->
      <div v-else class="tickets-grid">
        <div 
          v-for="ticket in tickets" 
          :key="ticket.id"
          class="ticket-card"
          @click="openTicketDetail(ticket)"
        >
          <!-- Ticket Header -->
          <div class="ticket-card__header">
            <div class="ticket-card__meta">
              <span class="ticket-card__id">#{{ ticket.id.substring(0, 8) }}</span>
              <span class="ticket-card__date">{{ formatDate(ticket.created_at) }}</span>
            </div>
            <div class="ticket-card__badges">
              <span class="badge" :class="getStatusClass(ticket.status)">
                {{ statuses[ticket.status] || ticket.status }}
              </span>
            </div>
          </div>

          <!-- Ticket Content -->
          <div class="ticket-card__content">
            <h3 class="ticket-card__subject">{{ ticket.subject }}</h3>
            <p class="ticket-card__body">{{ truncate(ticket.body, 120) }}</p>
          </div>

          <!-- Ticket Footer -->
          <div class="ticket-card__footer">
            <div class="ticket-card__info">
              <div v-if="ticket.category" class="ticket-card__category">
                <span class="category-badge">
                  {{ getCategoryDisplayName(ticket.category) }}
                </span>
                <span 
                  v-if="ticket.confidence" 
                  class="confidence-indicator"
                  :class="getConfidenceInfo(ticket.confidence).class"
                  :title="`Confidence: ${formatConfidence(ticket.confidence)}`"
                >
                  {{ getConfidenceInfo(ticket.confidence).text }}
                </span>
              </div>
              <div v-if="ticket.note" class="ticket-card__note-indicator">
                📝 Has Notes
              </div>
            </div>
            
            <div class="ticket-card__actions">
              <button 
                v-if="!ticket.category"
                @click.stop="classifyTicket(ticket)"
                :disabled="ticket.classifying"
                class="btn btn--sm btn--secondary"
              >
                {{ ticket.classifying ? 'Classifying...' : 'Classify' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.total > pagination.per_page" class="pagination">
        <button 
          @click="changePage(pagination.current_page - 1)"
          :disabled="!pagination.prev_page_url"
          class="btn btn--ghost btn--sm"
        >
          Previous
        </button>
        
        <span class="pagination__info">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
          ({{ pagination.total }} total)
        </span>
        
        <button 
          @click="changePage(pagination.current_page + 1)"
          :disabled="!pagination.next_page_url"
          class="btn btn--ghost btn--sm"
        >
          Next
        </button>
      </div>
    </div>

    <!-- New Ticket Modal -->
    <div v-if="showNewTicketForm" class="modal-overlay" @click="closeNewTicketForm">
      <div class="modal" @click.stop>
        <div class="modal__header">
          <h3 class="modal__title">Create New Ticket</h3>
          <button @click="closeNewTicketForm" class="modal__close">×</button>
        </div>
        
        <form @submit.prevent="createTicket" class="modal__body">
          <div class="form-group">
            <label for="subject" class="form-label">Subject</label>
            <input
              id="subject"
              v-model="newTicket.subject"
              type="text"
              required
              class="input"
              placeholder="Brief description of the issue"
            >
            <div v-if="errors.subject" class="form-error">{{ errors.subject }}</div>
          </div>
          
          <div class="form-group">
            <label for="body" class="form-label">Description</label>
            <textarea
              id="body"
              v-model="newTicket.body"
              required
              rows="5"
              class="textarea"
              placeholder="Detailed description of the issue..."
            ></textarea>
            <div v-if="errors.body" class="form-error">{{ errors.body }}</div>
          </div>
          
          <div class="modal__actions">
            <button type="button" @click="closeNewTicketForm" class="btn btn--ghost">
              Cancel
            </button>
            <button type="submit" :disabled="creating" class="btn btn--primary">
              {{ creating ? 'Creating...' : 'Create Ticket' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Ticket Detail Modal -->
    <div v-if="selectedTicket" class="modal-overlay" @click="closeTicketDetail">
      <div class="modal modal--large" @click.stop>
        <div class="modal__header">
          <h3 class="modal__title">Ticket Details</h3>
          <button @click="closeTicketDetail" class="modal__close">×</button>
        </div>
        
        <div class="modal__body">
          <TicketDetail 
            :ticket="selectedTicket"
            :statuses="statuses"
            :categories="categories"
            @update="handleTicketUpdate"
            @classify="handleTicketClassify"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../utils/api'
import { formatDate, truncate, getCategoryDisplayName, getStatusClass, getConfidenceInfo, formatConfidence, debounce, exportToCSV } from '../utils/helpers'
import TicketDetail from '../components/TicketDetail.vue'

export default {
  name: 'TicketsView',
  components: {
    TicketDetail
  },
  data() {
    return {
      // Data
      tickets: [],
      statuses: {},
      categories: [],
      pagination: null,
      
      // UI State
      loading: false,
      creating: false,
      showNewTicketForm: false,
      selectedTicket: null,
      
      // Filters
      searchQuery: '',
      statusFilter: '',
      categoryFilter: '',
      currentPage: 1,
      
      // New ticket form
      newTicket: {
        subject: '',
        body: ''
      },
      errors: {}
    }
  },
  computed: {
    hasFilters() {
      return this.searchQuery || this.statusFilter || this.categoryFilter
    }
  },
  mounted() {
    this.loadMeta()
    this.loadTickets()
    this.debouncedSearch = debounce(this.loadTickets, 500)
  },

methods: {
  // Helper methods
  formatDate,
  truncate,
  getCategoryDisplayName,
  getStatusClass,
  getConfidenceInfo,
  formatConfidence,

  // Local notification method
  showNotification(message, type = 'info') {
    console.log(`🔔 ${type.toUpperCase()}: ${message}`)
    
    if (type === 'error') {
      alert(`❌ Error: ${message}`)
    } else if (type === 'success') {
      console.log(`✅ ${message}`)
      // You could add a simple success indicator here
    } else if (type === 'warning') {
      console.log(`⚠️ ${message}`)
    }
  },

  async loadMeta() {
    try {
      const response = await fetch('/api/meta', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`)
      }
      
      const meta = await response.json()
      this.statuses = meta.statuses
      this.categories = meta.categories
    } catch (error) {
      console.error('Failed to load meta data:', error)
      this.showNotification('Failed to load meta data', 'error')
    }
  },

  async loadTickets() {
    this.loading = true
    
    try {
      const params = {
        page: this.currentPage,
        per_page: 20
      }
      
      if (this.searchQuery) params.search = this.searchQuery
      if (this.statusFilter) params.status = this.statusFilter
      if (this.categoryFilter) params.category = this.categoryFilter
      
      const response = await api.getTickets(params)
      this.tickets = response.data
      this.pagination = {
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total,
        prev_page_url: response.prev_page_url,
        next_page_url: response.next_page_url
      }
    } catch (error) {
      console.error('Failed to load tickets:', error)
      this.showNotification('Failed to load tickets', 'error')
    } finally {
      this.loading = false
    }
  },

  changePage(page) {
    if (page >= 1 && page <= this.pagination.last_page) {
      this.currentPage = page
      this.loadTickets()
    }
  },

  async createTicket() {
    this.creating = true
    this.errors = {}
    
    try {
      const newTicket = await api.createTicket(this.newTicket)
      this.showNotification('Ticket created successfully!', 'success')
      this.closeNewTicketForm()
      this.loadTickets()
    } catch (error) {
      console.error('Failed to create ticket:', error)
      if (error.data && error.data.errors) {
        this.errors = error.data.errors
      } else {
        this.showNotification('Failed to create ticket', 'error')
      }
    } finally {
      this.creating = false
    }
  },

async classifyTicket(ticket) {
  // Set loading state on the specific ticket using Vue 3 reactivity
  ticket.classifying = true
  
  try {
    await api.classifyTicket(ticket.id)
    this.showNotification('Classification job queued', 'success')
    
    // Refresh tickets after a short delay to show updated classification
    setTimeout(() => {
      this.loadTickets()
    }, 2000)
  } catch (error) {
    console.error('Failed to classify ticket:', error)
    this.showNotification('Failed to classify ticket', 'error')
  } finally {
    ticket.classifying = false
  }
},

  openTicketDetail(ticket) {
    this.selectedTicket = { ...ticket }
  },

  closeTicketDetail() {
    this.selectedTicket = null
  },

  closeNewTicketForm() {
    this.showNewTicketForm = false
    this.newTicket = { subject: '', body: '' }
    this.errors = {}
  },

  handleTicketUpdate(updatedTicket) {
    // Update the ticket in the list using Vue 3 reactivity
    const index = this.tickets.findIndex(t => t.id === updatedTicket.id)
    if (index !== -1) {
      this.tickets[index] = updatedTicket
    }
    this.selectedTicket = updatedTicket
    this.showNotification('Ticket updated successfully', 'success')
  },

  handleTicketClassify(ticket) {
    this.classifyTicket(ticket)
  },

  exportTickets() {
    if (!this.tickets.length) {
      this.showNotification('No tickets to export', 'warning')
      return
    }

    const exportData = this.tickets.map(ticket => ({
      id: ticket.id,
      subject: ticket.subject,
      body: ticket.body,
      status: ticket.status,
      category: ticket.category || '',
      confidence: ticket.confidence || '',
      explanation: ticket.explanation || '',
      note: ticket.note || '',
      created_at: ticket.created_at,
      updated_at: ticket.updated_at
    }))

    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    exportToCSV(exportData, `tickets-export-${timestamp}.csv`)
    this.showNotification('Tickets exported successfully', 'success')
  }
}
}
</script>