#target photoshop;
app.bringToFront();

// --- Configuration ---
var rootBookLayersFolder_str = "F:/0-bookcoverzone/free-book-covers/";
var outputPngFolder_str = "F:/0-bookcoverzone/free-book-full-covers/"; 

var actionName1 = "FreeCovers-MakeFull";
var actionName2 = "FreeCovers-MakeFull-2";
var actionName3 = "FreeCovers-MakeFull-3";
var actionSet = "Default Actions";
// --- End Configuration ---

function main() {
    var rootFolder = Folder(rootBookLayersFolder_str);
    var outputFolder = Folder(outputPngFolder_str);

    if (!rootFolder.exists) {
        alert("Error: Root book covers folder not found:\n" + rootBookLayersFolder_str);
        return;
    }

    // Create output folder if it doesn't exist
    if (!outputFolder.exists) {
        if (outputFolder.create()) {
            log("Output folder created: " + outputFolder.fsName);
        } else {
            alert("Error: Could not create output folder:\n" + outputFolder.fsName);
            return;
        }
    }

    // Get all JPG files in the root folder
    var jpgFiles = rootFolder.getFiles(function(f) { 
        return f instanceof File && 
               f.name.toLowerCase().match(/\.jpe?g$/); // Match .jpg or .jpeg extensions
    });

    if (jpgFiles.length === 0) {
        alert("No JPG files found in:\n" + rootBookLayersFolder_str);
        return;
    }

    alert("Starting batch process.\nFound " + jpgFiles.length + " JPG files to process.\n" +
          "This may take some time. Photoshop might appear unresponsive.");

    var processedCount = 0;
    var skippedCount = 0;
    var originalRulerUnits = app.preferences.rulerUnits;
    app.preferences.rulerUnits = Units.PIXELS; // Good practice for consistency

    for (var i = 0; i < jpgFiles.length; i++) {
        var currentJpgFile = jpgFiles[i];
        var jpgFileName = decodeURI(currentJpgFile.name);
        
        // Extract base name for output file (remove extension)
        var baseName = jpgFileName.replace(/\.[^\.]+$/, '');
        
        // Construct expected output JPG filenames
        var outputJpgFileName1 = baseName + "-front-mockup.jpg";
        var outputJpgFileName2 = baseName + "-front-mockup-2.jpg";
        var outputJpgFileName3 = baseName + "-front-mockup-3.jpg";
        
        var outputJpgFile1 = File(outputFolder.fsName + "/" + outputJpgFileName1);
        var outputJpgFile2 = File(outputFolder.fsName + "/" + outputJpgFileName2);
        var outputJpgFile3 = File(outputFolder.fsName + "/" + outputJpgFileName3);

        // --- CHECK IF ALL OUTPUT JPGs ALREADY EXIST ---
        if (outputJpgFile1.exists || outputJpgFile2.exists || outputJpgFile3.exists) {
            log("Skipping: At least output files for '" + baseName + "' already exist in " + outputFolder.name);
            skippedCount++;
            continue; // Move to the next JPG file
        }
        // --- END CHECK ---

        log("Processing: " + currentJpgFile.fsName);
        try {
            // 1. Open JPG file
            var doc = app.open(currentJpgFile);
            log("  - Opened cover file: " + jpgFileName);

            // Set up JPEG save options (used multiple times)
            var jpgSaveOptions = new JPEGSaveOptions();
            jpgSaveOptions.quality = 11; // Quality 0-12 scale, 9 is approximately 90%
            jpgSaveOptions.embedColorProfile = true;
            jpgSaveOptions.formatOptions = FormatOptions.STANDARDBASELINE;

            // 2. Run the first action and save
            try {
                app.doAction(actionName1, actionSet);
                log("  - Action '" + actionName1 + "' from set '" + actionSet + "' executed.");
                
                if (!outputJpgFile1.exists) {
                    doc.saveAs(outputJpgFile1, jpgSaveOptions, true, Extension.LOWERCASE);
                    log("  - Saved result as: " + outputJpgFile1.name);
                } else {
                    log("  - Output file 1 already exists, skipping save.");
                }
            } catch (actionError) {
                log("  - !!! ERROR running action '" + actionName1 + "': " + actionError);
                // If first action fails, we can't continue with the others
                doc.close(SaveOptions.DONOTSAVECHANGES);
                continue; // Skip to the next JPG file
            }

            // 3. Run the second action and save
            try {
                app.doAction(actionName2, actionSet);
                log("  - Action '" + actionName2 + "' from set '" + actionSet + "' executed.");
                
                if (!outputJpgFile2.exists) {
                    doc.saveAs(outputJpgFile2, jpgSaveOptions, true, Extension.LOWERCASE);
                    log("  - Saved result as: " + outputJpgFile2.name);
                } else {
                    log("  - Output file 2 already exists, skipping save.");
                }
            } catch (actionError) {
                log("  - !!! ERROR running action '" + actionName2 + "': " + actionError);
                // Continue to try the third action even if second fails
            }

            // 4. Run the third action and save
            try {
                app.doAction(actionName3, actionSet);
                log("  - Action '" + actionName3 + "' from set '" + actionSet + "' executed.");
                
                if (!outputJpgFile3.exists) {
                    doc.saveAs(outputJpgFile3, jpgSaveOptions, true, Extension.LOWERCASE);
                    log("  - Saved result as: " + outputJpgFile3.name);
                } else {
                    log("  - Output file 3 already exists, skipping save.");
                }
            } catch (actionError) {
                log("  - !!! ERROR running action '" + actionName3 + "': " + actionError);
            }

            // 5. Close document without saving
            doc.close(SaveOptions.DONOTSAVECHANGES);
            log("  - Closed document without saving changes.");

            processedCount++;

        } catch (e) {
            log("!!! ERROR processing file " + jpgFileName + ": " + e.toString() + " (Line: " + (e.line || 'N/A') + ")");
            // Ensure any open documents from this iteration are closed if an error occurred
            if (app.documents.length > 0) {
                var currentActiveDoc = app.activeDocument;
                if (currentActiveDoc.fullName.toString().toLowerCase() === currentJpgFile.fullName.toString().toLowerCase()) {
                    currentActiveDoc.close(SaveOptions.DONOTSAVECHANGES);
                    log("  - Cleaned up by closing file after error.");
                }
            }
        }
    }

    app.preferences.rulerUnits = originalRulerUnits; // Restore original ruler units
    alert("Batch process finished.\n" +
          "Processed " + processedCount + " files.\n" +
          "Skipped " + skippedCount + " because all output JPGs already existed.");
    log("--- Batch process finished. Processed " + processedCount + " files. Skipped " + skippedCount + " existing. ---");
}

// Helper function for logging
function log(message) {
    $.writeln(decodeURI(message)); // Writes to ExtendScript Toolkit Console
}

// Run the main function
try {
    main();
} catch(e) {
    alert("An unexpected error occurred: " + e.toString() + "\nLine: " + (e.line || 'N/A') + "\nFile: " + (e.fileName || 'N/A'));
    log("!!! TOP LEVEL ERROR: " + e.toString() + " (Line: " + (e.line || 'N/A') + ", File: " + (e.fileName || 'N/A') + ")");
}