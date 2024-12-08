</div>

    <script>
        let btn = document.querySelector(".bi-list");

        // Select the sidebar element with class 'sidebar'
        let sidebar = document.querySelector(".sidebar");

        // Add a click event listener to the button
        btn.addEventListener("click", () => {
        // Toggle the 'close' class on the sidebar, which will show or hide it based on CSS rules
        sidebar.classList.toggle("close");
        });

        // Select all elements with the class 'arrow' (likely used for expanding/collapsing submenu items)
        let arrows = document.querySelectorAll(".arrow");

        // Iterate over all selected arrow elements
        for (var i = 0; i < arrows.length; i++) {
          // Add a click event listener to each arrow
          arrows[i].addEventListener("click", (e) => {
            // Find the grandparent element of the clicked arrow (assumes a specific DOM structure)
            let arrowParent = e.target.parentElement.parentElement;

            // Toggle the 'show' class on the grandparent element, which will expand or collapse the submenu
            arrowParent.classList.toggle("show");
          });
        }

    </script>

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>



  </body>
</html>