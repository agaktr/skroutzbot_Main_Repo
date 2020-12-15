var gulp            = require('gulp');
var concat          = require('gulp-concat');
var uglify          = require('gulp-uglify-es').default;
var merge           = require('merge-stream');
var sass            = require('gulp-sass');
var autoprefixer    = require('gulp-autoprefixer');
var livereload      = require('gulp-livereload');
var gcmq            = require('gulp-group-css-media-queries');

sass.compiler = require('node-sass');


var assetsFolder = 'public/';

var config = {
    //Admin Assets folders
    srcAdminDirJS: assetsFolder + 'js/src',
    destAdminDirJS:  assetsFolder + 'js',
    srcAdminDirSASS:  assetsFolder + 'css/sass',
    destAdminDirSASS:  assetsFolder + 'css',
};


//The sass styles to compile combine and minify
var styles = [
    {
        src : [
            config.srcAdminDirSASS + '/base.scss'
        ],
        name : 'admin.min',
        dest : config.destAdminDirSASS + ''
    }
];


//The scripts to combine and minify
var scripts = [
    {
        src : [
            config.srcAdminDirJS + '/steps.js',
            config.srcAdminDirJS + '/userProfile.js',
            config.srcAdminDirJS + '/base.js'
        ],
        name : 'admin.min',
        dest : config.destAdminDirJS + ''
    }
];


//Task to Compile the styles, scripts are combined and minified
gulp.task('sass:compile', function () {

    var tasks = styles.map(function( file){
        return gulp.src( file.src )
            .pipe( concat(file.name + '.css' ) )
            .pipe(gcmq())
            .pipe( sass({outputStyle: 'compressed'}).on('error', sass.logError) )
            .pipe( autoprefixer({
                overrideBrowserslist: ['last 6 versions'],
                cascade: false
            }))
            .pipe( gulp.dest( file.dest )  )
            .pipe( livereload({ start: true }) );



    });

    return merge(tasks);


});


//Task to Compile the scripts, scripts are combined and minified
gulp.task('scripts:compile', function() {

    var tasks = scripts.map(function( file){
        return gulp.src( file.src )
            .pipe( concat(file.name + '.js' ) )
            .pipe( uglify().on('error', function(e){ console.log(e); } ) )
            .pipe( gulp.dest( file.dest ) )
            .pipe( livereload({ start: true }) );

    });

    return merge(tasks);

});


//Task to Watch only for changes in styles
gulp.task('sass:watch', function () {
    livereload.listen();
    return gulp.watch( config.srcAdminDirSASS + '/**/*.scss', gulp.series( 'sass:compile' ) );
});


//Task to Watch only for changes in scripts
gulp.task( 'scripts:watch', function() {
    livereload.listen();
    return gulp.watch( config.srcAdminDirJS + '/**/*.js', gulp.series( 'scripts:compile' ) );
});


//Task to Watch both styles and scripts
gulp.task('watch', function(done){

    gulp.watch( config.srcAdminDirSASS + '/**/*.scss', gulp.series( 'sass:compile' ) );
    gulp.watch( config.srcAdminDirJS + '/**/*.js', gulp.series( 'scripts:compile' ) );
    done();

});


//Change the default gulp task to compile styles and scripts and then watch
gulp.task( 'default', gulp.series( 'sass:compile', 'scripts:compile', 'watch' ) );
