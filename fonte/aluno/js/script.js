document.addEventListener('DOMContentLoaded', function() {
  // Add click handlers for content items
  const contentItems = document.querySelectorAll('.content-item');
  const suggestionItems = document.querySelectorAll('.suggestion-item');
  
  contentItems.forEach(item => {
    item.addEventListener('click', function() {
      // Toggle completion status
      const checkIcon = this.querySelector('.check-icon');
      if (!checkIcon.classList.contains('completed')) {
        checkIcon.classList.add('completed');
        checkIcon.textContent = '✓';
        
        // Add visual feedback
        this.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
          this.style.backgroundColor = '';
        }, 300);
      }
    });
  });
  
  suggestionItems.forEach(item => {
    item.addEventListener('click', function() {
      // Add click effect for suggestions
      this.style.backgroundColor = '#e3f2fd';
      setTimeout(() => {
        this.style.backgroundColor = '';
      }, 200);
    });
  });
  
  // Add hover effects for better UX
  const allClickableItems = [...contentItems, ...suggestionItems];
  
  allClickableItems.forEach(item => {
    item.addEventListener('mouseenter', function() {
      this.style.transform = 'translateX(2px)';
    });
    
    item.addEventListener('mouseleave', function() {
      this.style.transform = 'translateX(0)';
    });
  });
  
  // Simulate progress tracking
  function updateProgress() {
    const completedItems = document.querySelectorAll('.check-icon.completed').length;
    const totalItems = document.querySelectorAll('.content-item .check-icon').length;
    
    console.log(`Progress: ${completedItems}/${totalItems} items completed`);
  }
  
  // Call updateProgress whenever an item is completed
  contentItems.forEach(item => {
    item.addEventListener('click', updateProgress);
  });
});
