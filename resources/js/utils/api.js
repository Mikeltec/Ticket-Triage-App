// API utility functions for making HTTP requests

const API_BASE_URL = '/api'

// Helper function to handle API responses
const handleResponse = async (response) => {
  if (!response.ok) {
    const error = new Error(`HTTP error! status: ${response.status}`)
    error.status = response.status
    
    try {
      const errorData = await response.json()
      error.data = errorData
    } catch (e) {
      // Response might not be JSON
    }
    
    throw error
  }
  
  return response.json()
}

// Helper function to build query string
const buildQueryString = (params) => {
  const searchParams = new URLSearchParams()
  
  Object.keys(params).forEach(key => {
    if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
      searchParams.append(key, params[key])
    }
  })
  
  return searchParams.toString()
}

// API functions
export const api = {
  // Tickets
  async getTickets(params = {}) {
    const queryString = buildQueryString(params)
    const url = `${API_BASE_URL}/tickets${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    return handleResponse(response)
  },

  async createTicket(ticketData) {
    const response = await fetch(`${API_BASE_URL}/tickets`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(ticketData),
    })
    
    return handleResponse(response)
  },

  async getTicket(id) {
    const response = await fetch(`${API_BASE_URL}/tickets/${id}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    return handleResponse(response)
  },

  async updateTicket(id, updateData) {
    const response = await fetch(`${API_BASE_URL}/tickets/${id}`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(updateData),
    })
    
    return handleResponse(response)
  },

  async classifyTicket(id) {
    const response = await fetch(`${API_BASE_URL}/tickets/${id}/classify`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    })
    
    return handleResponse(response)
  },

  // Stats
  async getStats() {
    const response = await fetch(`${API_BASE_URL}/stats`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    return handleResponse(response)
  },

  // Meta data
  async getMeta() {
    const response = await fetch(`${API_BASE_URL}/meta`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    return handleResponse(response)
  }
}

export default api