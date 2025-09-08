// Helper utility functions

// Format date to readable string
export const formatDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffInMs = now - date
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))
  
  if (diffInDays === 0) {
    // Today - show time
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } else if (diffInDays === 1) {
    return 'Yesterday'
  } else if (diffInDays < 7) {
    return `${diffInDays} days ago`
  } else {
    return date.toLocaleDateString([], { 
      month: 'short', 
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  }
}

// Format full date and time
export const formatDateTime = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  return date.toLocaleDateString([], { 
    year: 'numeric',
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Truncate text to specified length
export const truncate = (text, length = 50) => {
  if (!text || text.length <= length) return text
  return text.substring(0, length).trim() + '...'
}

// Capitalize first letter
export const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

// Convert snake_case to Title Case
export const toTitleCase = (str) => {
  if (!str) return ''
  return str
    .split('_')
    .map(word => capitalize(word))
    .join(' ')
}

// Get status badge class
export const getStatusClass = (status) => {
  const statusClasses = {
    'open': 'badge--primary',
    'in_progress': 'badge--warning',
    'resolved': 'badge--success',
    'closed': 'badge--secondary'
  }
  return statusClasses[status] || 'badge--secondary'
}

// Get confidence level class and text
export const getConfidenceInfo = (confidence) => {
  if (!confidence) return { class: '', text: '' }
  
  const conf = parseFloat(confidence)
  
  if (conf >= 0.9) {
    return { class: 'confidence--high', text: 'High' }
  } else if (conf >= 0.7) {
    return { class: 'confidence--medium', text: 'Medium' }
  } else {
    return { class: 'confidence--low', text: 'Low' }
  }
}

// Debounce function for search input
export const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Generate random ID for temporary elements
export const generateId = () => {
  return Math.random().toString(36).substr(2, 9)
}

// Validate email format
export const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Format confidence as percentage
export const formatConfidence = (confidence) => {
  if (!confidence) return ''
  return `${Math.round(parseFloat(confidence) * 100)}%`
}

// Get category display name (convert snake_case to readable)
export const getCategoryDisplayName = (category) => {
  if (!category) return ''
  
  const categoryNames = {
    'technical_support': 'Technical Support',
    'billing_inquiry': 'Billing Inquiry',
    'feature_request': 'Feature Request',
    'bug_report': 'Bug Report',
    'account_access': 'Account Access',
    'general_inquiry': 'General Inquiry',
    'complaint': 'Complaint',
    'hardware_issue': 'Hardware Issue',
    'software_issue': 'Software Issue',
    'network_connectivity': 'Network Connectivity'
  }
  
  return categoryNames[category] || toTitleCase(category)
}

// Export CSV data
export const exportToCSV = (data, filename = 'export.csv') => {
  if (!data || data.length === 0) return
  
  // Get headers from first object
  const headers = Object.keys(data[0])
  
  // Create CSV content
  const csvContent = [
    headers.join(','), // Header row
    ...data.map(row => 
      headers.map(header => {
        const value = row[header] || ''
        // Escape commas and quotes
        return `"${String(value).replace(/"/g, '""')}"`
      }).join(',')
    )
  ].join('\n')
  
  // Create and trigger download
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  
  if (link.download !== undefined) {
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', filename)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}