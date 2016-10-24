/*jshint expr:true, -W083, esversion: 6, unused: false */
const gulp    = require("gulp");
const rename  = require("gulp-rename");
const csso    = require("gulp-csso");
const uglify  = require("gulp-uglify");
const sass    = require("gulp-sass");
const toc     = require("gulp-doctoc");
const postcss = require("gulp-postcss");
const assets  = require("postcss-assets");

gulp.task("styles", function () {
  return gulp.src([ "widgets/imagekit/assets/scss/*.scss" ])
    .pipe(sass())
    .pipe(postcss([
      assets({ loadPaths: ['widgets/imagekit/assets/images/'] }),
    ]))
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

gulp.task( 'readme', function() {
  return gulp.src(['readme.md'])
    .pipe(toc({
      mode: "github.com",
      title: "**Table of Contents**",
    }))
    .pipe(gulp.dest('.'));
});

gulp.task("default", ['styles', 'scripts', 'readme']);

gulp.task("watch", ['default'], () => {
  gulp.watch('widgets/imagekit/assets/scss/**/*.scss', [ 'styles' ]);
  gulp.watch('widgets/imagekit/assets/js/src/**/*.js', [ 'scripts' ]);
});
