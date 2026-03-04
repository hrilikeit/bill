var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on the button, open the modal
// btn.onclick = function() {
//   modal.style.display = "block";
// }

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

getPosts();

function getPosts(page = 1) {
  var entertainerId = document.getElementById('posts-bloke').getAttribute('data-entertainer');

  $.ajax({
    url: '?mode=Api&job=get_posts',
    type: 'GET',
    data: {
      entertainerId: entertainerId,
      page: page,
    },
    dataType: 'html',
    success: function(response) {
      var dataResponse = JSON.parse(response);
      document.getElementById('posts-bloke').innerHTML = dataResponse.data;
      pagination(dataResponse);
    },
    error: function(xhr, status, error) {
      console.error(xhr.responseText);
    }
  });
}

function pagination(dataResponse) {
  posts = dataResponse.data;
  currentPage = dataResponse.page;
  countPage = dataResponse.pages;

  document.getElementById('posts-bloke').innerHTML = posts;

  var htmlCode = '<div style="text-align: center">';

  htmlCode += '<button ' + (currentPage == 1 ? 'disabled ' : '') +'onclick="getPosts(' + (currentPage - 1) + ')">Previous Page</button>';

  if (currentPage > 3) {
    htmlCode += ' <button class="active" onclick="getPosts(1)">1</button>...';
  }
  for (var i = (currentPage - 2); i <= (currentPage + 2); i++) {
    if ( i > 0 && i<= countPage) {
      htmlCode += '<button class="active" ' + (currentPage == i ? 'style="color: red;"' : '') + ' onclick="getPosts(' + i + ')">' + i + '</button>';

    }
  }

  if (currentPage < (countPage - 2)) {
    htmlCode += '...<button class="active" onclick="getPosts(' + countPage + ')">' + countPage +'</button> ';
  }
  htmlCode += '<button ' + (currentPage == countPage ? 'disabled ' : '' ) +'onclick="getPosts(' + (currentPage + 1) + ')">Next Page</button>';
  htmlCode += '</div>'

  document.getElementById('posts-pagination').innerHTML = htmlCode;

}

