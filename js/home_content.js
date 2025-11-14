$(document).ready(function () {
  const $slider = $(".slider");
  const itemWidth = 260;
  const itemsPerPage = 3;
  const totalItems = $slider.children().length;
  let currentIndex = 0;

  $("#nextBtn").click(function () {
    if (currentIndex < totalItems - itemsPerPage) {
      currentIndex++;
      $slider.css("transform", `translateX(-${itemWidth * currentIndex}px)`);
    }
  });

  $("#prevBtn").click(function () {
    if (currentIndex > 0) {
      currentIndex--;
      $slider.css("transform", `translateX(-${itemWidth * currentIndex}px)`);
    }
  });
});
