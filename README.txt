# DBpedia Main Repository

This repository serves as the central hub for miscellaneous files and utilities related to the
DBpedia project. Over time, the project has grown into a modular collection of components, each
maintained in its own repository. The primary purpose of this root repository is to provide
branding assets (logos, documentation), top-level coordination, and links to other
sub-repositories.

## Repository Structure

- **`abstractExtraction/`**, **`dumpvalidator/`**, **`extraction/`**, etc. – core extraction and
  validation frameworks used for producing DBpedia datasets from Wikipedia dumps.
- **`ontology/`** – ontology files, mapping templates, and related scripts.
- **`pubby/`**, **`related_apps/`**, **`relfinder/`** – various applications and services built on
  top of DBpedia.
- **`tools/`**, **`tutorials/`**, and other folders provide utilities, examples, and educational
  material.

> ⚠️ **Note:** Many components are maintained in separate SCM repositories (e.g. GitHub or
> Mercurial) and may be mirrored here for convenience. Always check the specific project
> documentation for the most up-to-date information.

## Getting Started

1. **Explore subdirectories** to find the area of interest. Each major component typically contains
   its own README or documentation file.
2. **Build/Run** instructions vary by component; consult the respective README, `pom.xml`, or
   other build scripts.
3. **Reporting issues or contributing** should be done on the relevant project's issue tracker on
   GitHub (see links in submodule README files).

## Additional Resources

- Official website: https://dbpedia.org/
- Source repositories: https://github.com/dbpedia and previous Mercurial mirrors

Thank you for your interest in DBpedia! Contributions and feedback are welcome.

