// Photoshop Script: Batch Process Book Covers

#target photoshop
app.bringToFront();
app.preferences.rulerUnits = Units.PIXELS; // Set units to pixels

// --- Configuration ---
var SOURCE_FOLDER_PATH = "f:/0-bookcoverzone/free-book-full-covers";
var PSD_TEMPLATE_PATH = "f:/0-bookcoverzone/FreeCovers-Full.psd";
var OUTPUT_FOLDER_PATH = "f:/0-bookcoverzone/Processed-PNG-Covers"; // Output folder for PNGs

var ACTION_SET_NAME = "FreeKindleCovers";
var ACTION_FRONT = "FullCoverFront";
var ACTION_SPINE = "FullCoverSpine";
var ACTION_BACK = "FullCoverBack";
var ACTION_FLATTEN_RESIZE = "FullCoverFlattenAndResize";

var COVER_HEIGHT = 3360;
var FRONT_WIDTH = 2100;
var SPINE_WIDTH = 380;
var BACK_WIDTH = 2100;

// Calculated positions (assuming layout: BACK | SPINE | FRONT)
var BACK_X1 = 20;
var BACK_X2 = BACK_WIDTH;

var SPINE_X1 = BACK_WIDTH;
var SPINE_X2 = BACK_WIDTH + SPINE_WIDTH;

var FRONT_X1 = BACK_WIDTH + SPINE_WIDTH;
var FRONT_X2 = BACK_WIDTH + SPINE_WIDTH + FRONT_WIDTH;
// --- End Configuration ---

function main() {
    var sourceFolder = new Folder(SOURCE_FOLDER_PATH);
    var psdTemplateFile = new File(PSD_TEMPLATE_PATH);
    var outputFolder = new Folder(OUTPUT_FOLDER_PATH);

    if (!sourceFolder.exists) {
        alert("Source folder not found: " + SOURCE_FOLDER_PATH);
        return;
    }
    if (!psdTemplateFile.exists) {
        alert("PSD template not found: " + PSD_TEMPLATE_PATH);
        return;
    }

    if (!outputFolder.exists) {
        outputFolder.create();
        alert("Created output folder: " + OUTPUT_FOLDER_PATH);
    }

    var fileList = sourceFolder.getFiles(/\.(jpg|jpeg)$/i); // Get all JPG/JPEG files

    if (fileList.length === 0) {
        alert("No JPG files found in " + SOURCE_FOLDER_PATH);
        return;
    }

//    alert("Starting batch process for " + fileList.length + " JPG files.\n" +
//          "Output will be saved to: " + OUTPUT_FOLDER_PATH);

    for (var i = 0; i < fileList.length; i++) {
        var jpgFile = fileList[i];
        var jpgDoc;
        var psdDoc;

        try {
            // 1. Open the JPG
            jpgDoc = app.open(jpgFile);
//            if (jpgDoc.height !== COVER_HEIGHT || jpgDoc.width !== (FRONT_X2)) {
//                 alert("Warning: JPG " + jpgDoc.name + " has unexpected dimensions (" + jpgDoc.width + "x" + jpgDoc.height + "). Expected " + (FRONT_X2) + "x" + COVER_HEIGHT + ". Selections might be incorrect. Skipping this file.");
//                 jpgDoc.close(SaveOptions.DONOTSAVECHANGES);
//                 continue;
//            }


            // --- Process FRONT ---
            // 2. Select the right side (FRONT) and copy
            // Selection coordinates: [[left,top],[right,top],[right,bottom],[left,bottom]]
            jpgDoc.selection.select([
                [FRONT_X1, 0],
                [FRONT_X2, 0],
                [FRONT_X2, COVER_HEIGHT],
                [FRONT_X1, COVER_HEIGHT]
            ]);
            jpgDoc.selection.copy();

            // 3. Open PSD Template
            psdDoc = app.open(psdTemplateFile);
            app.activeDocument = psdDoc; // Ensure PSD is active

            // 4. Run FRONT action
            app.doAction(ACTION_FRONT, ACTION_SET_NAME);

            // --- Process SPINE ---
            // 5. Tab to opened cover (JPG)
            app.activeDocument = jpgDoc;

            // 6. Select the middle (SPINE) and copy
            jpgDoc.selection.select([
                [SPINE_X1, 0],
                [SPINE_X2, 0],
                [SPINE_X2, COVER_HEIGHT],
                [SPINE_X1, COVER_HEIGHT]
            ]);
            jpgDoc.selection.copy();

            // 7. Tab back to PSD
            app.activeDocument = psdDoc;

            // 8. Run SPINE action
            app.doAction(ACTION_SPINE, ACTION_SET_NAME);


            // --- Process BACK ---
            // 9. Tab to JPG
            app.activeDocument = jpgDoc;

            // 10. Select from left (BACK) and copy
            jpgDoc.selection.select([
                [BACK_X1, 0],
                [BACK_X2, 0],
                [BACK_X2, COVER_HEIGHT],
                [BACK_X1, COVER_HEIGHT]
            ]);
            jpgDoc.selection.copy();

            // 11. Tab back to PSD
            app.activeDocument = psdDoc;

            // 12. Run BACK action
            app.doAction(ACTION_BACK, ACTION_SET_NAME);

            // --- Finalize and Save ---
            // 13. Run FlattenAndResize action
            app.doAction(ACTION_FLATTEN_RESIZE, ACTION_SET_NAME);

            // 14. Save as PNG with low compression
            var outputFileName = jpgFile.name.replace(/\.[^\.]+$/, "") + ".png"; // Remove original extension, add .png
            var outputFile = new File(outputFolder + "/" + outputFileName);
            
            var webSaveOptions = new ExportOptionsSaveForWeb();
            webSaveOptions.format = SaveDocumentType.PNG;
            webSaveOptions.PNG8 = false; // Use PNG-24 for better quality
            webSaveOptions.transparency = true;
            webSaveOptions.interlaced = false;
            webSaveOptions.quality = 80; // Lower quality = faster saving

            psdDoc.exportDocument(outputFile, ExportType.SAVEFORWEB, webSaveOptions);

//            var pngSaveOptions = new PNGSaveOptions();
//            pngSaveOptions.compression = 1; // 0-9. 0 or 1 is low compression, fast. 9 is high compression, slow.
//            pngSaveOptions.interlaced = false;
//
//            var outputFileName = jpgFile.name.replace(/\.[^\.]+$/, "") + ".png"; // Remove original extension, add .png
//            var outputFile = new File(outputFolder + "/" + outputFileName);
//
//            psdDoc.saveAs(outputFile, pngSaveOptions, true, Extension.LOWERCASE);

            // --- Cleanup for next iteration ---
            if (psdDoc) {
                psdDoc.close(SaveOptions.DONOTSAVECHANGES); // Close the PSD without saving changes to the template
            }
            if (jpgDoc) {
                jpgDoc.close(SaveOptions.DONOTSAVECHANGES); // Close the JPG
            }

            // Optional: Log progress to ExtendScript Toolkit Console
            // $.writeln("Processed: " + jpgFile.name + " -> " + outputFileName);

        } catch (e) {
            alert("Error processing file: " + (jpgFile ? jpgFile.name : "Unknown") + "\nError: " + e.message + "\nLine: " + e.line);
            // Attempt to close any open documents to prevent issues with the next iteration
            if (psdDoc && !psdDoc.closed) {
                psdDoc.close(SaveOptions.DONOTSAVECHANGES);
            }
            if (jpgDoc && !jpgDoc.closed) {
                jpgDoc.close(SaveOptions.DONOTSAVECHANGES);
            }
            // You might want to 'continue;' here to skip to the next file or 'return;' to stop the script
            continue;
            //break;
        }
    }

//    alert("Batch process completed!");
}

// Run the main function
main();