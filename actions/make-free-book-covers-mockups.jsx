#target photoshop;
app.bringToFront();

// --- Configuration ---
var rootBookLayersFolder_str = "F:/0-bookcoverzone/free-book-covers/";
var mockupPsdFile_str = "F:/0-bookcoverzone/BcZ-Front-Mockup.psd";
var outputPngFolder_str = "F:/0-bookcoverzone/free-book-front-mockup/"; 

var actionName = "make-free-front-mockup";
var actionSet = "Default Actions";
// --- End Configuration ---

function main() {
    var rootFolder = Folder(rootBookLayersFolder_str);
    var mockupPsdFile = File(mockupPsdFile_str);
    var outputFolder = Folder(outputPngFolder_str);

    if (!rootFolder.exists) {
        alert("Error: Root book layers folder not found:\n" + rootBookLayersFolder_str);
        return;
    }
    if (!mockupPsdFile.exists) {
        alert("Error: Mockup PSD file not found:\n" + mockupPsdFile_str);
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
        
        // Construct expected output PNG filename
        var outputPngFileName = baseName + "-front-mockup.png";
        var outputPngFile = File(outputFolder.fsName + "/" + outputPngFileName);

        // --- CHECK IF OUTPUT PNG ALREADY EXISTS ---
        if (outputPngFile.exists) {
            log("Skipping: Output file '" + outputPngFileName + "' already exists in " + outputFolder.name);
            skippedCount++;
            continue; // Move to the next JPG file
        }
        // --- END CHECK ---

        log("Processing: " + currentJpgFile.fsName);
        try {
            // 1. Open JPG file
            var jpgDoc = app.open(currentJpgFile);

            // 2. Select All and Copy
            jpgDoc.selection.selectAll();
            jpgDoc.selection.copy();

            // 3. Close JPG file without saving
            jpgDoc.close(SaveOptions.DONOTSAVECHANGES);
            log("  - Copied JPG content and closed file.");

            // 4. Open Mockup PSD
            var mockupDoc = app.open(mockupPsdFile);
            log("  - Opened mockup PSD: " + mockupPsdFile.name);

            // 5. Run the action
            try {
                app.doAction(actionName, actionSet);
                log("  - Action '" + actionName + "' from set '" + actionSet + "' executed.");
            } catch (actionError) {
                log("  - !!! ERROR running action '" + actionName + "': " + actionError);
                // Close mockup PSD without saving if action failed, then continue to next
                mockupDoc.close(SaveOptions.DONOTSAVECHANGES);
                continue; // Skip to the next JPG file
            }

            // 6. Save the result as PNG
            var pngSaveOptions = new PNGSaveOptions();
            pngSaveOptions.compression = 6; // 0-9 (0=none, 9=max but slow), 6 is a good balance
            pngSaveOptions.interlaced = false;

            mockupDoc.saveAs(outputPngFile, pngSaveOptions, true, Extension.LOWERCASE);
            log("  - Saved mockup as: " + outputPngFile.name);

            // 7. Close Mockup PSD without saving
            mockupDoc.close(SaveOptions.DONOTSAVECHANGES);
            log("  - Closed mockup PSD without saving changes.");

            processedCount++;

        } catch (e) {
            log("!!! ERROR processing file " + jpgFileName + ": " + e.toString() + " (Line: " + (e.line || 'N/A') + ")");
            // Ensure any open documents from this iteration are closed if an error occurred mid-process
            if (app.documents.length > 0) {
                var currentActiveDoc = app.activeDocument;
                if (currentActiveDoc.fullName.toString().toLowerCase() === mockupPsdFile.fullName.toString().toLowerCase()) {
                    currentActiveDoc.close(SaveOptions.DONOTSAVECHANGES);
                    log("  - Cleaned up by closing mockup PSD after error.");
                } else if (currentActiveDoc.fullName.toString().toLowerCase() === currentJpgFile.fullName.toString().toLowerCase()) {
                    currentActiveDoc.close(SaveOptions.DONOTSAVECHANGES);
                    log("  - Cleaned up by closing JPG file after error.");
                }
            }
        }
    }

    app.preferences.rulerUnits = originalRulerUnits; // Restore original ruler units
    alert("Batch process finished.\n" +
          "Processed " + processedCount + " new mockups.\n" +
          "Skipped " + skippedCount + " because output PNG already existed.");
    log("--- Batch process finished. Processed " + processedCount + " new files. Skipped " + skippedCount + " existing. ---");
}

// Helper function for logging (can be expanded to write to a file)
function log(message) {
    $.writeln(decodeURI(message)); // Writes to ExtendScript Toolkit Console, decodeURI for better path display
}

// Run the main function
try {
    main();
} catch(e) {
    alert("An unexpected error occurred: " + e.toString() + "\nLine: " + (e.line || 'N/A') + "\nFile: " + (e.fileName || 'N/A'));
    log("!!! TOP LEVEL ERROR: " + e.toString() + " (Line: " + (e.line || 'N/A') + ", File: " + (e.fileName || 'N/A') + ")");
}