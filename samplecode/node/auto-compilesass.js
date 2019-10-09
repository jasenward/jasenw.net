/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * SRC: https://www.npmjs.com/package/gulp-sass
 */

//node is having a hard time locating the gulp module even though the path is set.
//fix later
'use strict';
 //C:\Users\Jasen\AppData\Roaming\npm\node_modules
var gulp = require('gulp');
var sass = require('gulp-sass');
var srcPathFilter = '../../suitecrm/themes/SuiteSteamPunk/css/**/*.scss';
var dstPath = '../../suitecrm/themes/SuiteSteamPunk/';

console.log('Starting up the SCSS Auto-Compiler');
 
gulp.task('sass', function () {
  return gulp.src(srcPathFilter)
    .pipe(sass.sync().on('error', sass.logError))
    .pipe(gulp.dest(dstPath));
});
 
gulp.task('sass:watch', function () {
  gulp.watch(srcPathFilter, ['sass']);
});
