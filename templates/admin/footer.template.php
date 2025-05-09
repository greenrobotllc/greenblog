        </div>
    </div>
    
    <script>
        // Simple confirmation for delete actions
        document.addEventListener('DOMContentLoaded', function() {
            const deleteLinks = document.querySelectorAll('.delete-confirm');
            
            deleteLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
