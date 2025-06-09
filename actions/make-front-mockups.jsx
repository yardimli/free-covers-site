// Photoshop Action Script: Batch Process Book Covers for Mockups

#target photoshop;
app.bringToFront();

// --- Configuration ---
var rootBookLayersFolder_str = "F:/0-bookcoverzone/book_layers/";
var mockupPsdFile_str = "F:/0-bookcoverzone/BcZ-Front-Mockup.psd";
var outputPngFolder_str = "F:/0-bookcoverzone/png24-front-mockup/"; 

var actionName = "make-front-mockup";
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
            log("Output folder created: " + outputFolder.fsName); // Changed alert to log for less interruption
        } else {
            alert("Error: Could not create output folder:\n" + outputFolder.fsName);
            return;
        }
    }

    var subFolders = rootFolder.getFiles(function(f) { return f instanceof Folder; });

    if (subFolders.length === 0) {
        alert("No subfolders found in:\n" + rootBookLayersFolder_str);
        return;
    }

    alert("Starting batch process.\nFound " + subFolders.length + " subfolders to check.\n" +
          "This may take some time. Photoshop might appear unresponsive.");

    var processedCount = 0;
    var skippedCount = 0;
    var originalRulerUnits = app.preferences.rulerUnits;
    app.preferences.rulerUnits = Units.PIXELS; // Good practice for consistency

    for (var i = 0; i < subFolders.length; i++) {
        var currentSubFolder = subFolders[i];
        var subFolderName = decodeURI(currentSubFolder.name); // e.g., "bookcover0032582"

        // Construct expected Kindle JPG filename
        var kindleJpgFileName = subFolderName + "_COVER_Kindle.jpg";
        var kindleJpgFile = File(currentSubFolder.fsName + "/" + kindleJpgFileName);

        // Construct expected output PNG filename
        var outputPngFileName = subFolderName + "-front-mockup.png";
        var outputPngFile = File(outputFolder.fsName + "/" + outputPngFileName);

        // --- CHECK IF OUTPUT PNG ALREADY EXISTS ---
        if (outputPngFile.exists) {
            log("Skipping: Output file '" + outputPngFileName + "' already exists in " + outputFolder.name);
            skippedCount++;
            continue; // Move to the next subfolder
        }
        // --- END CHECK ---

        if (kindleJpgFile.exists) {
            log("Processing: " + kindleJpgFile.fsName);
            try {
                // 1. Open Kindle JPG
                var kindleDoc = app.open(kindleJpgFile);

                // 2. Select All and Copy
                kindleDoc.selection.selectAll();
                kindleDoc.selection.copy();

                // 3. Close Kindle JPG without saving
                kindleDoc.close(SaveOptions.DONOTSAVECHANGES);
                log("  - Copied Kindle JPG content and closed file.");

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
                    continue; // Skip to the next subfolder
                }

                // 6. Save the result as PNG (outputPngFile is already defined)
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
                log("!!! ERROR processing folder " + subFolderName + ": " + e.toString() + " (Line: " + (e.line || 'N/A') + ")");
                // Ensure any open documents from this iteration are closed if an error occurred mid-process
                if (app.documents.length > 0) {
                    var currentActiveDoc = app.activeDocument;
                    if (currentActiveDoc.fullName.toString().toLowerCase() === mockupPsdFile.fullName.toString().toLowerCase()) {
                         currentActiveDoc.close(SaveOptions.DONOTSAVECHANGES);
                         log("  - Cleaned up by closing mockup PSD after error.");
                    } else if (currentActiveDoc.fullName.toString().toLowerCase() === kindleJpgFile.fullName.toString().toLowerCase()) {
                         currentActiveDoc.close(SaveOptions.DONOTSAVECHANGES);
                         log("  - Cleaned up by closing Kindle JPG after error.");
                    }
                }
            }
        } else {
            log("Skipping: Kindle JPG '" + kindleJpgFileName + "' not found in " + currentSubFolder.name);
            // Note: This 'skipped' is different from the one where output PNG exists.
            // You might want a separate counter or just rely on the log.
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
    // For a persistent log file, you could add:
    // var logFile = File(Folder.desktop + "/photoshop_batch_log.txt");
    // logFile.open("a");
    // logFile.encoding = "UTF-8";
    // logFile.writeln(new Date().toLocaleString() + ": " + decodeURI(message));
    // logFile.close();
}

// Run the main function
// It's good practice to wrap in a try-catch at the top level too
try {
    // Set Display Dialogs to NO to prevent Photoshop's own dialogs from interrupting (e.g. color profile mismatches)
    // Be cautious with this, as it might hide important warnings. Test thoroughly.
    // app.displayDialogs = DialogModes.NO;

    main();

} catch(e) {
    alert("An unexpected error occurred: " + e.toString() + "\nLine: " + (e.line || 'N/A') + "\nFile: " + (e.fileName || 'N/A'));
    log("!!! TOP LEVEL ERROR: " + e.toString() + " (Line: " + (e.line || 'N/A') + ", File: " + (e.fileName || 'N/A') + ")");
} finally {
    // app.displayDialogs = DialogModes.ALL; // Restore dialogs
}