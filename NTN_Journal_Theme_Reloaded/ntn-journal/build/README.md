NTN Journal Theme

Overview

This is the documentation for the NTN Journal WordPress theme.

Typography & Design

The theme uses a custom font stack designed to evoke a brutalist, hardware-focused aesthetic:

Body (font-sans): Manrope

Monospace (font-mono): Space Mono

Headings (font-display): Space Grotesk

Development & CSS Compilation

This theme uses Tailwind CSS v4. Since WordPress guidelines restrict the use of remote CSS resources (CDN), all styles must be compiled locally.

How to update styles:

Make changes to input.css or any .php template file.

Run the build script in the theme folder:

npm run build


The build process creates style-built.css.

Open style.css, keep the Theme Header comment at the very top, and paste the compiled CSS from style-built.css immediately below it.

File Structure

style.css: Main theme header + compiled Tailwind CSS.

input.css: Source file for custom styles and Tailwind directives.

functions.php: Theme logic, helper functions, and custom post meta.

single.php: Template for single posts with dynamic Author Card and Review Box.