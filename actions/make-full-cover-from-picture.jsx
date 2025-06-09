#target photoshop;
app.bringToFront();

// --- Configuration ---
var rootBookLayersFolder_str = "F:/GitHub/FreeKindleCovers/actions/InputImages/";
var outputPngFolder_str = "F:/GitHub/FreeKindleCovers/actions/OutputImages/";
var templatePsdPath_str = "F:/GitHub/FreeKindleCovers/actions/empty-full-cover.psd"; // <--- !!! IMPORTANT: SET THIS PATH !!!

// Actions for initial processing of the source JPG
var ActionSet = "FreeKindleCovers";

var resizeActionName = "ResizeForFullCoverFront";

// Actions for processing the template PSD
var placeActionName = "PlaceImageOnFullCoverMiddle"; // Assumes this action pastes from clipboard or places a linked file
var fillActionName = "FillFullCover";

// Actions for subsequent mockups on the template (as per your original script)
var mockupActionName2 = "FreeCovers-MakeFull-2"; // Renamed from actionName2 for clarity
var mockupActionName3 = "FreeCovers-MakeFull-3"; // Renamed from actionName3 for clarity
// --- End Configuration ---

function main() {
	var rootFolder = Folder(rootBookLayersFolder_str);
	var outputFolder = Folder(outputPngFolder_str);
	var templatePsdFile = File(templatePsdPath_str);
	
	if (!rootFolder.exists) {
		alert("Error: Root book covers folder not found:\n" + rootBookLayersFolder_str);
		return;
	}
	if (!templatePsdFile.exists) {
		alert("Error: Template PSD file not found:\n" + templatePsdPath_str);
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
	var errorCount = 0;
	var originalRulerUnits = app.preferences.rulerUnits;
	app.preferences.rulerUnits = Units.PIXELS; // Good practice for consistency
	
	// Set up JPEG save options (used multiple times)
	var jpgSaveOptions = new JPEGSaveOptions();
	jpgSaveOptions.quality = 11; // Quality 0-12 scale
	jpgSaveOptions.embedColorProfile = true;
	jpgSaveOptions.formatOptions = FormatOptions.STANDARDBASELINE;
	
	for (var i = 0; i < jpgFiles.length; i++) {
		var currentJpgFile = jpgFiles[i];
		var jpgFileName = decodeURI(currentJpgFile.name);
		var baseName = jpgFileName.replace(/\.[^\.]+$/, '');
		
		// Construct expected output JPG filenames
		var outputJpgFileName1 = baseName + "-1-full-cover.jpg";
		var outputJpgFileName2 = baseName + "-2-full-cover.jpg";
		var outputJpgFileName3 = baseName + "-3-full-cover.jpg";
		
		var outputJpgFile1 = File(outputFolder.fsName + "/" + outputJpgFileName1);
		var outputJpgFile2 = File(outputFolder.fsName + "/" + outputJpgFileName2);
		var outputJpgFile3 = File(outputFolder.fsName + "/" + outputJpgFileName3);
		
		// --- CHECK IF ANY OUTPUT JPGs ALREADY EXIST ---
		// If any of the three target files exist, skip this source image.
		if (outputJpgFile1.exists || outputJpgFile2.exists || outputJpgFile3.exists) {
			log("Skipping: At least one output file for '" + baseName + "' already exists in " + outputFolder.name);
			skippedCount++;
			continue; // Move to the next JPG file
		}
		// --- END CHECK ---
		
		log("Processing: " + currentJpgFile.fsName);
		var sourceDoc = null;
		var templateDoc = null;
		
		//try {
			// --- STAGE 1: Process Source JPG ---
			log("  - Stage 1: Processing source JPG...");
			sourceDoc = app.open(currentJpgFile);
			log("    - Opened source cover file: " + jpgFileName);
			
			try {
				app.doAction(resizeActionName, ActionSet);
				log("    - Action '" + resizeActionName + "' from set '" + ActionSet + "' executed on source.");
			} catch (actionError) {
				log("    - !!! ERROR running action '" + resizeActionName + "' on source: " + actionError);
				errorCount++;
				if (sourceDoc) sourceDoc.close(SaveOptions.DONOTSAVECHANGES);
				continue; // Skip to the next JPG file
			}
			
			// Copy the processed image to clipboard
			// This assumes your "PlaceImageOnFullCover" action will use the clipboard content.
			// If it uses "File > Place...", this step might need adjustment or the action needs to be clipboard-aware.
			sourceDoc.selection.selectAll();
			sourceDoc.selection.copy();
			log("    - Copied processed source image to clipboard.");
			
			sourceDoc.close(SaveOptions.DONOTSAVECHANGES);
			sourceDoc = null;
			log("    - Closed source cover without saving changes.");
			// --- END STAGE 1 ---
			
			
			// --- STAGE 2: Process Template PSD ---
			log("  - Stage 2: Processing with template PSD...");
			templateDoc = app.open(templatePsdFile);
			log("    - Opened template PSD: " + templatePsdFile.name);
			
			// Mockup 1: Place, Fill, Save
			try {
				app.doAction(placeActionName, ActionSet);
				log("    - Action '" + placeActionName + "' from set '" + ActionSet + "' executed on template.");
			} catch (actionError) {
				log("    - !!! ERROR running action '" + placeActionName + "' on template: " + actionError);
				errorCount++;
				if (templateDoc) templateDoc.close(SaveOptions.DONOTSAVECHANGES);
				continue; // Skip to the next JPG file
			}
			
			try {
				app.doAction(fillActionName, ActionSet);
				log("    - Action '" + fillActionName + "' from set '" + ActionSet + "' executed on template.");
			} catch (actionError) {
				log("    - !!! ERROR running action '" + fillActionName + "' on template: " + actionError);
				errorCount++;
				if (templateDoc) templateDoc.close(SaveOptions.DONOTSAVECHANGES);
				continue; // Skip to the next JPG file
			}
			
			if (!outputJpgFile1.exists) { // Double check, though outer check should cover
				templateDoc.saveAs(outputJpgFile1, jpgSaveOptions, true, Extension.LOWERCASE);
				log("    - Saved result as: " + outputJpgFile1.name);
			} else {
				log("    - Output file 1 '" + outputJpgFile1.name + "' already exists (should have been caught by pre-check). Skipping save.");
			}
			
			// Mockup 2: Run action, Save
			try {
				app.doAction(mockupActionName2, ActionSet);
				log("    - Action '" + mockupActionName2 + "' from set '" + ActionSet + "' executed on template.");
				if (!outputJpgFile2.exists) {
					templateDoc.saveAs(outputJpgFile2, jpgSaveOptions, true, Extension.LOWERCASE);
					log("    - Saved result as: " + outputJpgFile2.name);
				} else {
					log("    - Output file 2 '" + outputJpgFile2.name + "' already exists. Skipping save.");
				}
			} catch (actionError) {
				log("    - !!! ERROR running action '" + mockupActionName2 + "': " + actionError);
				// Continue to try the third action even if second fails, but log an error
				errorCount++; // Increment error count for this specific action failure
			}
			
			// Mockup 3: Run action, Save
			try {
				app.doAction(mockupActionName3, ActionSet);
				log("    - Action '" + mockupActionName3 + "' from set '" + ActionSet + "' executed on template.");
				if (!outputJpgFile3.exists) {
					templateDoc.saveAs(outputJpgFile3, jpgSaveOptions, true, Extension.LOWERCASE);
					log("    - Saved result as: " + outputJpgFile3.name);
				} else {
					log("    - Output file 3 '" + outputJpgFile3.name + "' already exists. Skipping save.");
				}
			} catch (actionError) {
				log("    - !!! ERROR running action '" + mockupActionName3 + "': " + actionError);
				errorCount++; // Increment error count for this specific action failure
			}
			
			templateDoc.close(SaveOptions.DONOTSAVECHANGES);
			templateDoc = null;
			log("    - Closed template PSD without saving changes.");
			// --- END STAGE 2 ---
			
			processedCount++;
			
		//} catch (e) {
		//	log("!!! OVERALL ERROR processing file " + jpgFileName + ": " + e.toString() + " (Line: " + (e.line || 'N/A') + ")");
		//	errorCount++;
		//	// Ensure any open documents from this iteration are closed if an error occurred
		//	if (sourceDoc && !sourceDoc.closed) {
		//		sourceDoc.close(SaveOptions.DONOTSAVECHANGES);
		//		log("  - Cleaned up by closing sourceDoc after error.");
		//	}
		//	if (templateDoc && !templateDoc.closed) {
		//		templateDoc.close(SaveOptions.DONOTSAVECHANGES);
		//		log("  - Cleaned up by closing templateDoc after error.");
		//	}
		//}
	}
	
	app.preferences.rulerUnits = originalRulerUnits; // Restore original ruler units
	alert("Batch process finished.\n" +
		"Successfully processed " + processedCount + " files.\n" +
		"Skipped " + skippedCount + " because output JPGs already existed.\n" +
		(errorCount > 0 ? "Encountered " + errorCount + " errors during processing. Check log." : "No errors encountered during processing steps."));
	log("--- Batch process finished. Processed " + processedCount + " files. Skipped " + skippedCount + " existing. Errors: " + errorCount + " ---");
}

// Helper function for logging
function log(message) {
	$.writeln(decodeURI(message)); // Writes to ExtendScript Toolkit Console
	// For more permanent logging, you could write to a file:
	// var logFile = File("~/Desktop/photoshop_batch_log.txt");
	// logFile.open("a");
	// logFile.writeln(new Date().toTimeString() + ": " + decodeURI(message));
	// logFile.close();
}

// Run the main function
try {
	main();
} catch(e) {
	alert("An UNEXPECTED SCRIPT-LEVEL error occurred: " + e.toString() + "\nLine: " + (e.line || 'N/A') + "\nFile: " + (e.fileName || 'N/A'));
	log("!!! TOP LEVEL SCRIPT ERROR: " + e.toString() + " (Line: " + (e.line || 'N/A') + ", File: " + (e.fileName || 'N/A') + ")");
}
