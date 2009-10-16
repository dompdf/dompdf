Summary: TrueType to Adobe Type 1 font converter
Name: ttf2pt1
Version: 3.4.4
Release: 1jv
Source: %{name}-%{version}.tgz
Copyright: Distributable
Group: Utilities/Printing
BuildRoot: /var/tmp/ttf2pt1

%description
 * True Type Font to Adobe Type 1 font converter 
 * By Mark Heath <mheath@netspace.net.au> 
 * Based on ttf2pfa by Andrew Weeks <ccsaw@bath.ac.uk> 
 * With help from Frank M. Siegert <fms@this.net> 

%prep 
%setup

%build
make all

%install
rm -fr $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/local/bin
mkdir -p $RPM_BUILD_ROOT/usr/local/share/%{name}
mkdir -p $RPM_BUILD_ROOT/usr/local/doc

install -s -m 0555 ttf2pt1 $RPM_BUILD_ROOT/usr/local/bin
install -m 0555 scripts/* $RPM_BUILD_ROOT/usr/local/share/%{name}
chmod 0444 $RPM_BUILD_ROOT/usr/local/share/%{name}/convert.cfg.sample

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644, root, root, 755)
%doc README README.html INSTALL INSTALL.html
/usr/local/bin/ttf2pt1
/usr/local/share/%{name}

