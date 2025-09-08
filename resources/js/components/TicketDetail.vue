<template>
  <div class="ticket-detail">
    <!-- Ticket Header -->
    <div class="ticket-detail__header">
      <div class="ticket-detail__meta">
        <span class="ticket-detail__id">#{{ ticket.id.substring(0, 8) }}</span>
        <span class="ticket-detail__date">{{ formatDateTime(ticket.created_at) }}</span>
      </div>
      <div class="ticket-detail__status">
        <select 
          v-model="editableTicket.status" 
          @change="updateTicket({ status: editableTicket.status })"
          class="select"
        >
          <option v-for="(label, status) in statuses" :key="status" :value="status">
            {{ label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Ticket Content -->
    <div class="ticket-detail__content">
      <div class="ticket-detail__section">
        <h3 class="ticket-detail__subject">{{ ticket.subject }}</h3>
        <div class="ticket-detail__body">{{ ticket.body }}</div>
      </div>

      <!-- AI Classification Section -->
      <div class="ticket-detail__section">
        <div class="section-header">
          <h4 class="section-title">AI Classification</h4>
          <button 
            @click="runClassification"
            :disabled="classifying"
            class="btn btn--sm btn--secondary"
          >
            {{ classifying ? 'Classifying...' : 'Run Classification' }}
          </button>
        </div>

        <div class="classification-info">
          <!-- Category -->
          <div class="form-group">
            <label for="category" class="form-label">Category</label>
            <select 
              id="category"
              v-model="editableTicket.category"
              @change="updateTicket({ category: editableTicket.category })"
              class="select"
            >
              <option value="">Select category...</option>
              <option v-for="category in categories" :key="category" :value="category">
                {{ getCategoryDisplayName(category) }}
              </option>
            </select>
          </div>

          <!-- Explanation (Read-only) -->
          <div v-if="ticket.explanation" class="form-group">
            <label class="form-label">AI Explanation</label>
            <div class="explanation-box">
              {{ ticket.explanation }}
            </div>
          </div>

          <!-- Confidence (Read-only) -->
          <div v-if="ticket.confidence" class="form-group">
            <label class="form-label">Confidence Level</label>
            <div class="confidence-display">
              <div class="confidence-bar">
                <div 
                  class="confidence-bar__fill"
                  :class="getConfidenceInfo(ticket.confidence).class"
                  :style="{ width: (ticket.confidence * 100) + '%' }"
                ></div>
              </div>
              <span class="confidence-text">
                {{ formatConfidence(ticket.confidence) }} 
                ({{ getConfidenceInfo(ticket.confidence).text }})
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Internal Notes Section -->
      <div class="ticket-detail__section">
        <div class="section-header">
          <h4 class="section-title">Internal Notes</h4>
        </div>

        <div class="form-group">
          <label for="note" class="form-label">Staff Notes</label>
          <textarea
            id="note"
            v-model="editableTicket.note"
            @blur="updateNote"
            rows="4"
            class="textarea"
            placeholder="Add internal notes for staff members..."
          ></textarea>
          <div class="form-help">
            These notes are only visible to staff members
          </div>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="ticket-detail__section">
        <h4 class="section-title">Timeline</h4>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-item__icon">📝</div>
            <div class="timeline-item__content">
              <div class="timeline-item__title">Ticket Created</div>
              <div class="timeline-item__time">{{ formatDateTime(ticket.created_at) }}</div>
            </div>
          </div>
          
          <div v-if="ticket.updated_at !== ticket.created_at" class="timeline-item">
            <div class="timeline-item__icon">✏️</div>
            <div class="timeline-item__content">
              <div class="timeline-item__title">Last Updated</div>
              <div class="timeline-item__time">{{ formatDateTime(ticket.updated_at) }}</div>
            </div>
          </div>

          <div v-if="ticket.category" class="timeline-item">
            <div class="timeline-item__icon">🤖</div>
            <div class="timeline-item__content">
              <div class="timeline-item__title">
                AI Classified as "{{ getCategoryDisplayName(ticket.category) }}"
              </div>
              <div v-if="ticket.confidence" class="timeline-item__subtitle">
                Confidence: {{ formatConfidence(ticket.confidence) }}
              </div>
            </div>
          </div>

          <div v-if="ticket.note" class="timeline-item">
            <div class="timeline-item__icon">📋</div>
            <div class="timeline-item__content">
              <div class="timeline-item__title">Staff Note Added</div>
              <div class="timeline-item__subtitle">{{ truncate(ticket.note, 100) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../utils/api'
import { formatDateTime, getCategoryDisplayName, getConfidenceInfo, formatConfidence, truncate } from '../utils/helpers'

export default {
  name: 'TicketDetail',
  props: {
    ticket: {
      type: Object,
      required: true
    },
    statuses: {
      type: Object,
      required: true
    },
    categories: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      editableTicket: { ...this.ticket },
      classifying: false,
      updating: false,
      noteTimeout: null
    }
  },
  watch: {
    ticket: {
      handler(newTicket) {
        this.editableTicket = { ...newTicket }
      },
      deep: true
    }
  },
  methods: {
    // Helper methods
    formatDateTime,
    getCategoryDisplayName,
    getConfidenceInfo,
    formatConfidence,
    truncate,

    async updateTicket(updateData) {
      if (this.updating) return
      
      this.updating = true
      
      try {
        const updatedTicket = await api.updateTicket(this.ticket.id, updateData)
        this.$emit('update', updatedTicket)
      } catch (error) {
        console.error('Failed to update ticket:', error)
        this.showNotification('Failed to update ticket', 'error')
        
        // Revert changes on error
        this.editableTicket = { ...this.ticket }
      } finally {
        this.updating = false
      }
    },

    updateNote() {
      // Debounce note updates to avoid too many API calls
      if (this.noteTimeout) {
        clearTimeout(this.noteTimeout)
      }
      
      this.noteTimeout = setTimeout(() => {
        if (this.editableTicket.note !== this.ticket.note) {
          this.updateTicket({ note: this.editableTicket.note })
        }
      }, 1000)
    },

    async runClassification() {
      this.classifying = true
      
      try {
        await api.classifyTicket(this.ticket.id)
        this.showNotification('Classification job queued', 'success')
        this.$emit('classify', this.ticket)
      } catch (error) {
        console.error('Failed to classify ticket:', error)
        this.showNotification('Failed to classify ticket', 'error')
      } finally {
        this.classifying = false
      }
    },

    // Local notification method
    showNotification(message, type = 'info') {
      // Try to access parent notification method
      if (this.$parent && this.$parent.$parent && this.$parent.$parent.showNotification) {
        this.$parent.$parent.showNotification(message, type)
        return
      }
      
      // Fallback to console
      console.log(`${type.toUpperCase()}: ${message}`)
      
      if (type === 'error') {
        alert(`Error: ${message}`)
      }
    }
  }
}
</script>