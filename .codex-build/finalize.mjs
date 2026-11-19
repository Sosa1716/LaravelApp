import path from 'node:path';
import { pathToFileURL } from 'node:url';
const skill='C:/Users/vegag/.codex/plugins/cache/openai-primary-runtime/presentations/26.1105.12137/skills/presentations';
const {finalizePresentation}=await import(pathToFileURL(path.join(skill,'container_tools/artifact_tool_utils.mjs')).href);
const root=path.resolve('.'); const build=path.join(root,'.codex-build'); const final=path.join(root,'Presentacion','Propuesta_Gestion_Restaurante.pptx');
const result=await finalizePresentation({workspaceDir:root,candidatePath:path.join(build,'propuesta-restaurante-draft.pptx'),finalPath:final,pythonExecutable:'C:/Users/vegag/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe',integrityValidatorPath:path.join(skill,'container_tools/inspect_presentation_package_integrity.py'),layoutValidatorPath:path.join(skill,'container_tools/inspect_presentation_layout_geometry.py'),layoutArgs:['--expected-slide-size-emu','12192000,6858000','--validate-bullet-geometry','--validate-heading-fit'],requiredNativeTableOwnerSlides:[],verifyArtifactToolImport:true,receiptPath:path.join(build,'validation.json')});
console.log(JSON.stringify(result));

