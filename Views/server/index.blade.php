<!DOCTYPE html>
<html lang="en">
    <head>
        <link href='https://bootswatch.com/simplex/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body style='margin: 50px 15px 15px 15px'>
        <div class='container'>
            <div class='panel panel-default'>
                <div class='panel-heading'>README.md</div>
                <div class='panel-body'>
                    {!! Parsedown::instance()->text($content) !!}
                </div>
            </div>
        </div>
    </body>
</html>
