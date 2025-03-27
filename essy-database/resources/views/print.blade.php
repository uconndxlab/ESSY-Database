<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ESSY Index')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <main class="print-main-wrap">

        <img src="{{ asset('assets/essy-logo.png') }}" class="essy-logo"alt="ESSY Logo"/>

        <p class="print-text-main"> 
        The ESSY Whole Child Screener is a measure to provide a holistic snapshot of each student. It
            assesses both individual student characteristics as well as conditions of the student's environment.
            There are six broad domains of focus:
        </p>

        

        <section class="domain-sections">
            <article class="academic-domain domain"></article>
            <article class="physical-domain domain"></article>
            <article class="attendance-domain domain"></article>
            <article class="sewb-domain domain"></article>
            <article class="behavior-domain domain"></article>
            <article class="sos-domain domain"></article>
        </section>





    </main>
        
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>