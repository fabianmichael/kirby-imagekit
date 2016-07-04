/*jshint expr:true, -W083, esversion: 6, unused: false */
const gulp   = require("gulp");
const rename = require("gulp-rename");
const csso   = require("gulp-csso");
const uglify = require("gulp-uglify");
const sass   = require("gulp-sass");

gulp.task("styles", function () {
  return gulp.src([ "widgets/imagekit/assets/scss/*.scss" ])
    .pipe(sass())
    .pipe(csso())
    .pipe(rename({ suffix: ".min" }))
    .pipe(gulp.dest("widgets/imagekit/assets/css"));
});

gulp.task("scripts", function () {
  return gulp.src([ "widgets/imagekit/assets/js/src/*.js" ])
    .pipe(uglify())
    .pipe(rename({ suffix: ".min" }))
    .pipe(gulp.dest("widgets/imagekit/assets/js/dist"));
});

gulp.task("default", ['styles', 'scripts']);
