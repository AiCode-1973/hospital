    </div><!-- /.admin-content -->
  </main>
</div><!-- /.admin-wrap -->

<script>
(function () {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (sidebar.classList.contains('open')
          && !sidebar.contains(e.target)
          && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }
  // Auto-remover flash após 4 s
  setTimeout(() => document.querySelectorAll('.flash').forEach(el => el.remove()), 4000);
})();
</script>
</body>
</html>
